<?php

namespace ParserGenerator\GrammarNode;

class Branch extends \ParserGenerator\GrammarNode\BaseNode implements \ParserGenerator\GrammarNode\BranchInterface
{
    static $spc = '';
    public $ignoreWhitespaces = false;

    protected $parser;
    protected $nodeName;
    protected $node;
    public $startCharsCache;

    public function __construct($nodeName)
    {
        $this->nodeName = $nodeName;
    }

    public function rparse($string, $fromIndex = 0, $restrictedEnd = array())
    {
        $cacheStr = $fromIndex . '-' . $this->nodeName . '-' . implode(',', $restrictedEnd);
        $lastResult = 31;

        if (isset($this->parser->cache[$cacheStr])) {
            if (is_int($this->parser->cache[$cacheStr])) {
                $this->parser->cache[$cacheStr] = false;
            } else {
                return $this->parser->cache[$cacheStr];
            }
        } else {
            $this->parser->cache[$cacheStr] = 0;
        }
        beforeForeach:
        //$this->getRoute(substr($string, $fromIndex, 1));
        foreach ($this->node as $_optionIndex => $option) {
            $subnodes = array();
            $optionIndex = 0;
            $indexes = array(-1 => $fromIndex);
            $optionCount = count($option);
            //!!! TODO:
            for($i =0; $i < $optionCount; $i++) {
                $restrictedEnds[$i] = array();
            }
            $restrictedEnds[$optionCount - 1] = $restrictedEnd;
            while (true) {
                $subNode = $option[$optionIndex]->rparse($string, $indexes[$optionIndex - 1], $restrictedEnds[$optionIndex]);
                if ($subNode) {
                    $subNodeOffset = $subNode['offset'];
                    $subnodes[$optionIndex] = $subNode['node'];
                    $restrictedEnds[$optionIndex][$subNodeOffset] = $subNodeOffset;
                    $indexes[$optionIndex] = $subNodeOffset;
                    if (++$optionIndex === $optionCount) {
                        break;
                    };
                } elseif ($optionIndex-- === 0) {
                    continue 2;
                }
            }
            // match
            $index = $indexes[$optionCount - 1];
            $node = new \ParserGenerator\SyntaxTreeNode\Branch($this->nodeName, $_optionIndex, $subnodes);
            $r = array('node' => $node, 'offset' => $index);
            $this->parser->cache[$cacheStr] = $r;
            if ($r != $lastResult) {
                $lastResult = $r;
                goto beforeForeach;
            }
            return $r;
        }
        return false;
    }

    public function canBeEmpty()
    {
        if (!isset($this->canBeEmptyCache)) {
            $canBeEmptyMap = array();
            do {
                $oldMap = $canBeEmptyMap;
                foreach ($this->parser->grammar as $name => $node) {
                    if ($node instanceof \ParserGenerator\GrammarNode\BranchInterface) {
                        $nodeCanBeEmpty = false;
                        foreach ($node->getNode() as $sequence) {
                            $seqCanBeEmpty = true;
                            foreach ($sequence as $item) {
                                if ($item instanceof \ParserGenerator\GrammarNode\BranchInterface && $item->getParser() === $this->parser) {
                                    if (!isset($canBeEmptyMap[$item->getNodeName()])) {
                                        $seqCanBeEmpty = null;
                                    } elseif ($canBeEmptyMap[$item->getNodeName()] === false) {
                                        $seqCanBeEmpty = false;
                                        break;
                                    }
                                } else {
                                    if ($item->canBeEmpty() === false) {
                                        $seqCanBeEmpty = false;
                                        break;
                                    }
                                }
                            }

                            if ($seqCanBeEmpty) {
                                $nodeCanBeEmpty = true;
                                break;
                            } elseif ($seqCanBeEmpty === null) {
                                $nodeCanBeEmpty = null;
                            }
                        }

                        $canBeEmptyMap[$node->getNodeName()] = $nodeCanBeEmpty;
                    }
                }
            } while ($canBeEmptyMap != $oldMap);

            foreach ($this->parser->grammar as $node) {
                if ($node instanceof \ParserGenerator\GrammarNode\BranchInterface) {
                    //unsolvable circural reference should be resolved to false
                    $node->_setCanBeEmptyCache((bool)$canBeEmptyMap[$node->getNodeName()]);
                }
            }
        }

        return $this->canBeEmptyCache;
    }

    public function startChars()
    {
        if (!isset($this->startCharsCache)) {
            $nodesChars = array();
            do {
                $oldNodesChars = $nodesChars;

                foreach ($this->parser->grammar as $name => $node) {
                    if ($node instanceof \ParserGenerator\GrammarNode\BranchInterface) {
                        if (!isset($nodesChars[$name])) {
                            $nodesChars[$name] = array();
                        }
                        foreach ($node->getNode() as $sequence) {
                            foreach ($sequence as $item) {
                                if ($item instanceof \ParserGenerator\GrammarNode\BranchInterface && $item->getParser() === $this->parser) {
                                    if (isset($nodesChars[$item->getNodeName()])) {
                                        $nodesChars[$name] += $nodesChars[$item->getNodeName()];
                                    }
                                } else {
                                    $nodesChars[$name] = array_replace($nodesChars[$name], $item->startChars());
                                }

                                if (!$item->canBeEmpty()) {
                                    break;
                                }
                            }
                        }
                    }
                }
            } while ($oldNodesChars !== $nodesChars);

            foreach ($nodesChars as $name => $cache) {
                $this->parser->grammar[$name]->_setStartCharsCache($cache);
            }
        }

        return $this->startCharsCache;
    }

    protected function getRoute($letter)
    {
        if (!isset($this->routeCache)) {
            $this->routeCache = array();
            for ($i = 0; $i < 256; $i++) {
                $this->routeCache[chr($i)] = array();
            }

            foreach ($this->node as $seqIndex => $sequence) {
                $seqStartChars = array();
                $canBeEmpty = true;

                foreach ($sequence as $item) {
                    $seqStartChars = array_replace($seqStartChars, $item->startChars());
                    if ($item->canBeEmpty() === false) {
                        $canBeEmpty = false;
                        break;
                    }
                }

                if ($canBeEmpty) {
                    $seqStartChars = \ParserGenerator\GrammarNode\BaseNode::startChars();
                }

                foreach ($seqStartChars as $char => $_) {
                    $this->routeCache[$char][$seqIndex] = $sequence;
                }
            }
        }

        return $this->routeCache[$letter];
    }

    public function setParser($parser)
    {
        $this->parser = $parser;
    }

    public function getParser()
    {
        return $this->parser;
    }

    public function setNode($node)
    {
        $this->node = $node;
    }

    public function getNode()
    {
        return $this->node;
    }

    public function getNodeName()
    {
        return $this->nodeName;
    }

    public function _setCanBeEmptyCache($value)
    {
        $this->canBeEmptyCache = $value;
    }

    public function _setStartCharsCache($value)
    {
        $this->startCharsCache = $value;
    }

    public function __toString()
    {
        return $this->getNodeName();
    }
}