<?php declare(strict_types=1);

namespace ParserGenerator;

use ParserGenerator\GrammarNode\Branch;
use ParserGenerator\GrammarNode\BranchInterface;
use ParserGenerator\GrammarNode\ErrorTrackDecorator;
use ParserGenerator\GrammarNode\Lookahead;
use ParserGenerator\GrammarNode\NodeInterface;
use ParserGenerator\GrammarNode\PredefinedString;
use ParserGenerator\GrammarNode\Regex;
use ParserGenerator\GrammarNode\TextS;
use ParserGenerator\SyntaxTreeNode\Root;

class Parser
{
    /** @var array */
    public $cache;
    /** @var array */
    public $grammar = [];
    /** @var array */
    public $options;

    protected function buildFromArray(array $grammar, array $options)
    {
        $this->grammar = $grammar;
        $this->options = $options;

        foreach ($this->grammar as $name => $node) {
            $grammarNode = new Branch($name);
            $grammarNode->setParser($this);
            $grammarNode = new ErrorTrackDecorator($grammarNode);
            $this->grammar[$name] = $grammarNode;
        }

        $this->grammar['string'] = new PredefinedString(true);

        foreach ($grammar as $name => $node) {
            $grammarNodeOptions = [];
            foreach ($node as $optionIndex => $option) {
                foreach ((array)$option as $seqIndex => $seq) {
                    if (is_string($seq)) {
                        if (substr($seq, 0, 1) == ':') {
                            if (substr($seq, 1, 1) == '/') {
                                $grammarNodeOptions[$optionIndex][$seqIndex] = new ErrorTrackDecorator(new Regex(substr($seq,
                                    1), true));
                            } else {
                                $grammarNodeOptions[$optionIndex][$seqIndex] = $this->grammar[substr($seq, 1)];
                            }
                        } else {
                            $grammarNodeOptions[$optionIndex][$seqIndex] = new ErrorTrackDecorator(new TextS($seq));
                        }
                    } elseif ($seq instanceof NodeInterface) {
                        $grammarNodeOptions[$optionIndex][$seqIndex] = new ErrorTrackDecorator($seq);
                    } else {
                        throw new Exception('incorrect sequenceitem');
                    }
                }
            }
            $this->grammar[$name]->getDecoratedNode()->setNode($grammarNodeOptions);
        }
    }

    public function iterateOverNodes(callable $callback)
    {
        $visitedNodes = [];
        foreach ($this->grammar as $node) {
            $this->_iterateOverNodes($node, $callback, $visitedNodes);
        }
    }

    protected function _iterateOverNodes(NodeInterface $node, callable $callback, array &$visitedNodes)
    {
        $hash = spl_object_hash($node);

        if (isset($visitedNodes[$hash])) {
            return;
        }

        $visitedNodes[$hash] = true;
        $callback($node);

        if ($node instanceof ErrorTrackDecorator) {
            return $this->_iterateOverNodes($node->getDecoratedNode(), $callback, $visitedNodes);
        }

        if (method_exists($node, 'getNode')) {
            /** @var BranchInterface $node */
            foreach ($node->getNode() as $sequence) {
                foreach ($sequence as $subnode) {
                    $this->_iterateOverNodes($subnode, $callback, $visitedNodes);
                }
            }
        } elseif (method_exists($node, 'getUsedNodes')) {
            /** @var Lookahead $node */
            foreach ($node->getUsedNodes() as $subnode) {
                $this->_iterateOverNodes($subnode, $callback, $visitedNodes);
            }
        }
    }

    protected function buildFromString(string $grammar, array $options)
    {
        if (!isset($options['errorTrack'])) {
            $options['trackError'] = true;
        }
        $this->options = $options;
        $options['parser'] = $this;
        $this->grammar = GrammarParser::getInstance()->buildGrammar($grammar, $options);
    }

    /**
     * @param array|string $grammar
     * @param array $options
     */
    public function __construct($grammar, array $options = [])
    {
        if (is_array($grammar)) {
            $this->buildFromArray($grammar, $options);
        } else {
            $this->buildFromString($grammar, $options);
        }

        /*$that = $this;
        $this->iterateOverNodes(function($node) use ($that) {
            if ($node instanceof \ParserGenerator\GrammarNode\BranchInterface && empty($that->grammar[$node->getNodeName()])) {
                $that->grammar[$node->getNodeName()] = $node;
            }
        });*/
    }

    /**
     * @param string $string
     * @param string $nodeToParseName
     * @return Root|bool When successfully parsed, returns a Root node, otherwise false
     *                   In such a case, use getErrorString() to get more details.
     */
    public function parse(string $string, string $nodeToParseName = 'start')
    {
        $this->iterateOverNodes(function (NodeInterface $node) {
            if ($node instanceof ErrorTrackDecorator) {
                $node->reset();
            } elseif (isset($node->lastMatch)) {
                $node->lastMatch = -1;
                $node->lastNMatch = -1;
            }
        });
        $this->cache = [];
        $restrictedEnd = [];
        if (!empty($this->options['ignoreWhitespaces'])) {
            $trimmedString = ltrim($string);
            $beforeContent = substr($string, 0, strlen($string) - strlen($trimmedString));
            $string = $trimmedString;
        } else {
            $beforeContent = '';
        }

        for ($i = strlen($string) - 1; $i > -1; $i--) {
            $restrictedEnd[$i] = $i;
        }
        $rparseResult = $this->grammar[$nodeToParseName]->rparse($string, 0, $restrictedEnd);

        if ($rparseResult) {
            $result = Root::createFromPrototype($rparseResult['node']);
            $result->setBeforeContent($beforeContent);

            return $result;
        }

        return false;
    }

    public function getError(): array
    {
        $maxMatch = -1;
        $match = [];
        $this->iterateOverNodes(function (NodeInterface $node) use (&$maxMatch, &$match) {
            if ($node instanceof ErrorTrackDecorator) {
                if ($maxMatch < $node->getMaxCheck()) {
                    $maxMatch = $node->getMaxCheck();
                    $match = [];
                }

                if ($maxMatch === $node->getMaxCheck()) {
                    $node = $node->getDecoratedNode();

                    if ($node instanceof GrammarNode\Series) {
                        $node = $node->getMainNode();
                    }

                    $match[] = $node;
                };
            } /*elseif (isset($node->lastMatch)) {
                if ($maxMatch < $node->lastMatch) {
                    $maxMatch = $node->lastMatch;
                    $match = array();
                }

                if ($maxMatch === $node->lastMatch) {
                    if ($node instanceof GrammarNode\Series) {
                        $node = $node->getMainNode();
                    }

                    $match[] = $node;
                };
            }*/
        });

        if ($maxMatch === -1) {
            return [
                'index' => 0,
                'expected' => [$this->grammar['start']],
            ];
        }

        return [
            'index' => $maxMatch,
            'expected' => $match,
        ];
    }

    protected static function getLineAndCharacterFromOffset(string $str, int $offset): array
    {
        $lines = preg_split('/(\r\n|\n\r|\r|\n)/', substr($str, 0, $offset));
        return [
            'line' => count($lines),
            'char' => strlen($lines[count($lines) - 1]) + 1,
        ];
    }

    /**
     * @param string $str The same input used for parse()
     * @return string
     */
    public function getErrorString(string $str): string
    {
        $error = $this->getError();

        $posData = self::getLineAndCharacterFromOffset($str, $error['index']);

        $expected = implode(' or ', $this->generalizeErrors($error['expected']));
        $foundLength = 20;
        $found = substr($str, $error['index']);
        if (strlen($found) > $foundLength) {
            $found = substr($found, 0, $foundLength) . '...';
        }

        return "line: " . $posData['line'] . ', character: ' . $posData['char'] . "\nexpected: " . $expected . "\nfound: " . $found;
    }

    protected function generalizeErrors(array $errors)
    {
        $errorsByHash = [];
        foreach ($errors as $error) {
            $errorsByHash[spl_object_hash($error)] = $error;
        }

        foreach ($errorsByHash as $hash => $error) {
            if (isset($errorsByHash[$hash]) && $error instanceof BranchInterface) {
                $this->parse('', $error->getNodeName());
                $errorData = $this->getError();
                foreach ($errorData['expected'] as $errorToRemove) {
                    if ($errorToRemove !== $error) {
                        unset($errorsByHash[spl_object_hash($errorToRemove)]);
                    }
                }
            }
        }

        return array_values($errorsByHash);
    }
}
