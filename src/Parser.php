<?php declare(strict_types=1);

namespace ParserGenerator;

class Parser
{
    public $cache;
    public $grammar = [];

    public $maxIndex;
    public $expected;
    public $options;

    protected function buildFromArray($grammar, $options)
    {
        $this->grammar = $grammar;
        $this->options = $options;

        foreach ($this->grammar as $name => $node) {
            $grammarNode = new \ParserGenerator\GrammarNode\Branch($name);
            $grammarNode->setParser($this);
            $grammarNode = new \ParserGenerator\GrammarNode\ErrorTrackDecorator($grammarNode);
            $this->grammar[$name] = $grammarNode;
        }

        $this->grammar['string'] = new \ParserGenerator\GrammarNode\PredefinedString(true);

        foreach ($grammar as $name => $node) {
            $grammarNodeOptions = [];
            foreach ($node as $optionIndex => $option) {
                foreach ((array)$option as $seqIndex => $seq) {
                    if (is_string($seq)) {
                        if (substr($seq, 0, 1) == ':') {
                            if (substr($seq, 1, 1) == '/') {
                                $grammarNodeOptions[$optionIndex][$seqIndex] = new \ParserGenerator\GrammarNode\ErrorTrackDecorator(new \ParserGenerator\GrammarNode\Regex(substr($seq,
                                    1), true));
                            } else {
                                $grammarNodeOptions[$optionIndex][$seqIndex] = $this->grammar[substr($seq, 1)];
                            }
                        } else {
                            $grammarNodeOptions[$optionIndex][$seqIndex] = new \ParserGenerator\GrammarNode\ErrorTrackDecorator(
                                new \ParserGenerator\GrammarNode\TextS($seq));
                        }
                    } elseif ($seq instanceof \ParserGenerator\GrammarNode\NodeInterface) {
                        $grammarNodeOptions[$optionIndex][$seqIndex] = new \ParserGenerator\GrammarNode\ErrorTrackDecorator($seq);
                    } else {
                        throw new Exception('incorrect sequenceitem');
                    }
                }
            }
            $this->grammar[$name]->getDecoratedNode()->setNode($grammarNodeOptions);
        }
    }

    public function iterateOverNodes($callback)
    {
        $visitedNodes = [];
        foreach ($this->grammar as $node) {
            $this->_iterateOverNodes($node, $callback, $visitedNodes);
        }
    }

    protected function _iterateOverNodes($node, $callback, &$visitedNodes)
    {
        $hash = spl_object_hash($node);

        if (empty($visitedNodes[$hash])) {
            $visitedNodes[$hash] = true;
            $callback($node);

            if ($node instanceof GrammarNode\ErrorTrackDecorator) {
                return $this->_iterateOverNodes($node->getDecoratedNode(), $callback, $visitedNodes);
            } elseif (method_exists($node, 'getNode')) {
                foreach ($node->getNode() as $sequence) {
                    foreach ($sequence as $subnode) {
                        $this->_iterateOverNodes($subnode, $callback, $visitedNodes);
                    }
                }
            } elseif (method_exists($node, 'getUsedNodes')) {
                foreach ($node->getUsedNodes() as $subnode) {
                    $this->_iterateOverNodes($subnode, $callback, $visitedNodes);
                }
            }
        }
    }

    protected function buildFromString($grammar, $options)
    {
        if (!isset($options['errorTrack'])) {
            $options['trackError'] = true;
        }
        $this->options = $options;
        $options['parser'] = $this;
        $this->grammar = \ParserGenerator\GrammarParser::getInstance()->buildGrammar($grammar, $options);
    }

    public function __construct($grammar, $options = [])
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

    public function parse($string, $nodeToParseName = 'start')
    {
        $this->iterateOverNodes(function ($node) {
            if ($node instanceof \ParserGenerator\GrammarNode\ErrorTrackDecorator) {
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
            $result = \ParserGenerator\SyntaxTreeNode\Root::createFromPrototype($rparseResult['node']);
            $result->setBeforeContent($beforeContent);

            return $result;
        } else {
            return false;
        }
    }

    public function getError()
    {
        $maxMatch = -1;
        $match = [];
        $this->iterateOverNodes(function ($node) use (&$maxMatch, &$match) {
            if ($node instanceof \ParserGenerator\GrammarNode\ErrorTrackDecorator) {
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

    public static function getLineAndCharacterFromOffset($str, $offset)
    {
        $lines = preg_split('/(\r\n|\n\r|\r|\n)/', substr($str, 0, $offset));
        return [
            'line' => count($lines),
            'char' => strlen($lines[count($lines) - 1]) + 1,
        ];
    }

    public function getErrorString($str)
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

    public function generate($length, $node = 'start')
    {
        return GrammarStringGenerator::generate($this->grammar[$node], $length);
    }

    public function generalizeErrors($errors)
    {
        $errorsByHash = [];
        foreach ($errors as $error) {
            $errorsByHash[spl_object_hash($error)] = $error;
        }

        foreach ($errorsByHash as $hash => $error) {
            if (isset($errorsByHash[$hash]) && $error instanceof \ParserGenerator\GrammarNode\BranchInterface) {
                $this->parse('', $error->getNodeName());
                $errorData = $this->getError();
                foreach($errorData['expected'] as $errorToRemove) {
                    if ($errorToRemove !== $error) {
                        unset($errorsByHash[spl_object_hash($errorToRemove)]);
                    }
                }
            }
        }

        return array_values($errorsByHash);
    }
}
