<?php declare(strict_types=1);

namespace ParserGenerator;

use ParserGenerator\GrammarNode\Branch;
use ParserGenerator\GrammarNode\BranchFactory;
use ParserGenerator\GrammarNode\BranchInterface;
use ParserGenerator\GrammarNode\ErrorTrackDecorator;
use ParserGenerator\GrammarNode\Lookahead;
use ParserGenerator\GrammarNode\NodeInterface;
use ParserGenerator\GrammarNode\PredefinedString;
use ParserGenerator\GrammarNode\Regex;
use ParserGenerator\GrammarNode\TextS;
use ParserGenerator\SyntaxTreeNode\Root;
use ParserGenerator\Util\Error;

class Parser
{
    /** @var array */
    public $cache;
    /** @var array */
    public $grammar = [];
    /** @var array */
    public $options;
    /** @var string */
    public $lastParsed;
    /** @var array|string */
    public $grammarSource;

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
        $this->options = $options;
        $this->grammar = GrammarParser::getInstance()->buildGrammar($grammar, $options);
    }

    /**
     * @param array|string $grammar
     * @param array $options
     */
    public function __construct($grammar, array $options = [])
    {
        $options += $this->getDefaultOptions();

        // TODO: don't missuse $options to pass around the parser
        $options['parser'] = $this;
        $this->grammarSource = $grammar;

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
     * @param string               $string
     * @param string|NodeInterface $nodeToParse node to parse or its name
     * @return Root|bool When successfully parsed, returns a Root node, otherwise false
     *                   In such a case, use getErrorString() to get more details.
     */
    public function parse(string $string, $nodeToParse = 'start')
    {
        $this->lastParsed = $string;
        $nodeToParse = is_string($nodeToParse) ? $this->grammar[$nodeToParse] : $nodeToParse;
        
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
        $rparseResult = $nodeToParse->rparse($string, 0, $restrictedEnd);

        if ($rparseResult) {
            $result = Root::createFromPrototype($rparseResult['node']);
            $result->setBeforeContent($beforeContent);

            return $result;
        }

        return false;
    }

    public function getException(Error $errorUtil = null): ParsingException
    {
        return ($errorUtil ?: new Error())->getError($this, $this->lastParsed);
    }

    public function getDefaultOptions(): array
    {
        return [
            'caseInsensitive' => false,
            'defaultBranchType' => BranchFactory::FULL,
            'ignoreWhitespaces' => false,
            'trackError' => true,
            'backtracer' => null,
        ];
    }
}
