<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

class PEGNoCacheBranch extends \ParserGenerator\GrammarNode\Branch
{
    public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
    {
        foreach ($this->node as $_optionIndex => $option) {
            $index = $fromIndex;
            $subnodes = [];

            foreach ($option as $sequenceItem) {
                $subnode = $sequenceItem->rparse($string, $index, []);
                if ($subnode) {
                    $subnodes[] = $subnode['node'];
                    $index = $subnode['offset'];
                } else {
                    continue 2;
                }
            }

            $node = new \ParserGenerator\SyntaxTreeNode\Branch($this->nodeShortName, $_optionIndex, $subnodes);
            $r = ['node' => $node, 'offset' => $index];
            return isset($restrictedEnd[$index]) ? false : $r;
        }

        return false;
    }
}