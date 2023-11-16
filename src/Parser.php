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

/**
 * Genaral class for managing pasing ang parser creation process
 * Main interface to comunicate with whole library
 */
class Parser
{
    /**
     * all fields in this class are internal and should not be used outside this lib implementation
     */
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

    /**
     * this builds parser from $grammar array
     * this is used mainly for parsing provided grammar in string format
     * however it may be used to build target grammar
     * 
     * $grammar format:
     * each entry in $grammar array is a branchNode (key is a branch name)
     * branchNode has one or more options / production rules - those production rules may be named - key in array is a name)
     * production rules is a sequence of termial or non-termial represented by string or NodeInterface
     * 
     * for example following grammar:
     *   start :=> start b
     *         :=> "c".
     *   b     :=> /abc/.
     * 
     * can be passed as:
     * ['start' => [[':start', ':b'], 
     *              ['c']], 
     *  'b'     => [['/abc/']]]
     * note that:
     *   - branch references starts with :
     *   - regular expressions starts with /
     *   - all other strings are converted into text nodes
     * we may also pass NodeInterface object instead of string
     * 
     * @param (string|NodeInterface)[][][] $grammar
     * @param array $options
     * @throws Exception
     */
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

    /**
     * internal - iterate over parser nodes to call callback on each node
     * 
     * @param callable $callback
     */
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
     * fill options with default values and builds parser using proper way (from array or from string)
     * 
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
     * method for initalising, running and finalising parsing process on a provided string
     * 
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
            preg_match('/\s*/', $string, $match, 0, 0);
            $beforeContent = $match[0];
            $startPos      = strlen($beforeContent);
        } else {
            $beforeContent = '';
            $startPos      = 0;
        }

        for ($i = strlen($string) - 1; $i > -1; $i--) {
            $restrictedEnd[$i] = $i;
        }
        $rparseResult = $nodeToParse->rparse($string, $startPos, $restrictedEnd);

        if ($rparseResult) {
            $result = Root::createFromPrototype($rparseResult['node']);
            $result->setBeforeContent($beforeContent);

            return $result;
        }

        return false;
    }

    /**
     * this method returns ParsingException - object which represents informations about where and how parsing process failed
     * this method should be called only if parsing process failed (parse($string) retured false) and only if 'trackError' option is set to true (default option value)
     * 
     * @param Error|null $errorUtil
     */
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
