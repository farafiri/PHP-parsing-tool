<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

class NaiveBranch extends \ParserGenerator\GrammarNode\Branch
{
    public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
    {
        $cacheStr = $fromIndex . '-' . $this->nodeName . '-' . implode(',', $restrictedEnd);

        if (isset($this->parser->cache[$cacheStr])) {
            return $this->parser->cache[$cacheStr];
        }
        $this->parser->cache[$cacheStr] = false;

        foreach ($this->node as $_optionIndex => $option) {
            $subnodes = [];
            $optionIndex = 0;
            $indexes = [-1 => $fromIndex];
            $optionCount = count($option);
            //!!! TODO:
            $restrictedEnds = [[], [], [], [], [], [], [], []];
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
            return $r;
        }
        return false;
    }
}
