<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

class Branch extends \ParserGenerator\GrammarNode\BaseNode implements \ParserGenerator\GrammarNode\BranchInterface
{
    static $spc = '';
    public $ignoreWhitespaces = false;

    protected $nodeName;
    protected $nodeShortName;
    protected $node;
    public $startCharsCache;

    public function __construct($nodeName)
    {
        $this->nodeName = $nodeName;
        $this->nodeShortName = $nodeName;
    }

    public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
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
        foreach ($this->node as $_optionIndex => $option) {
            $subnodes = [];
            $optionIndex = 0;
            $indexes = [-1 => $fromIndex];
            $optionCount = count($option);
            //!!! TODO:
            $restrictedEnds = array_fill(0, $optionCount - 1, []);
            $restrictedEnds[$optionCount - 1] = $restrictedEnd;
            while (true) {
                $subNode = $option[$optionIndex]->rparse($string, $indexes[$optionIndex - 1],
                    $restrictedEnds[$optionIndex]);
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
            $node = new \ParserGenerator\SyntaxTreeNode\Branch($this->nodeShortName, $_optionIndex, $subnodes);
            $r = ['node' => $node, 'offset' => $index];
            $this->parser->cache[$cacheStr] = $r;
            if ($r != $lastResult) {
                $lastResult = $r;
                goto beforeForeach;
            }
            return $r;
        }
        return false;
    }

    public function setParser(\ParserGenerator\Parser $parser)
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

    public function __toString()
    {
        return $this->getNodeName();
    }

    public function copy($copyCallback)
    {
        $copy = clone $this;
        $copy->setNode($copyCallback($this->node));
        $copy->nodeName = $this->nodeName . '&' . spl_object_hash($copy);
        return $copy;
    }
}
