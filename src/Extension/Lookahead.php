<?php declare(strict_types=1);

namespace ParserGenerator\Extension;

use ParserGenerator\Exception;

class Lookahead extends \ParserGenerator\Extension\SequenceItem
{
    protected function getGrammarGrammarSequence()
    {
        $noWhiteChar = new \ParserGenerator\GrammarNode\WhitespaceNegativeContextCheck(null);
        $whiteChar = new \ParserGenerator\GrammarNode\WhitespaceContextCheck(null);
        $operator = ':/[!?]/';

        return [
            [$operator, $noWhiteChar, ':sequenceItem', $whiteChar, ':sequenceItem'],
            [':sequenceItem', $whiteChar, $operator, $noWhiteChar, ':sequenceItem'],
            [$operator, $noWhiteChar, ':sequenceItem'],
        ];
    }

    protected function _buildSequenceItem(&$grammar, $sequenceItem, $grammarParser, $options)
    {
        switch ($this->getDetailTypeIndex($sequenceItem)) {
            case 0:
                $mainNode = $grammarParser->buildSequenceItem($grammar, $sequenceItem->getSubnode(4), $options);
                $lookaheadNode = $grammarParser->buildSequenceItem($grammar, $sequenceItem->getSubnode(2), $options);
                $operator = (string)$sequenceItem->getSubnode(0);
                $before = true;

                break;
            case 1:
                $mainNode = $grammarParser->buildSequenceItem($grammar, $sequenceItem->getSubnode(0), $options);
                $lookaheadNode = $grammarParser->buildSequenceItem($grammar, $sequenceItem->getSubnode(4), $options);
                $operator = (string)$sequenceItem->getSubnode(2);
                $before = false;

                break;
            case 2:
                $mainNode = null;
                $lookaheadNode = $grammarParser->buildSequenceItem($grammar, $sequenceItem->getSubnode(2), $options);
                $operator = (string)$sequenceItem->getSubnode(0);
                $before = null;

                break;

            default:
                throw new Exception('that was unexpected');
        }
        
        $lookacheadBool = \ParserGenerator\Util\LeafNodeConverter::getBoolOrNullFromNode($lookaheadNode);
        
        if ($lookacheadBool !== null) {
            if ($operator == '!') {
                $lookacheadBool = !$lookacheadBool;
            }
            
            if ($lookacheadBool === false) {
                return new \ParserGenerator\GrammarNode\BooleanNode(false);
            }
            
            return $mainNode ?? new \ParserGenerator\GrammarNode\BooleanNode(true);
        }

        return new \ParserGenerator\GrammarNode\Lookahead($lookaheadNode, $mainNode, $before, $operator == '?');
    }
}
