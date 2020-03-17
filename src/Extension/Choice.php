<?php declare(strict_types=1);

namespace ParserGenerator\Extension;

class Choice extends \ParserGenerator\Extension\SequenceItem
{
    protected $seqName = 'itemsListSeparatedByVerticalBar';

    public function extendGrammar($grammarGrammar)
    {
        $grammarGrammar[$this->seqName] = [
            'nest' => [':sequence', '|', (':' . $this->seqName)],
            'last' => [':sequence'],
        ];

        return parent::extendGrammar($grammarGrammar);
    }

    protected function getGrammarGrammarSequence()
    {
        return ['(', (':' . $this->seqName), ':comments', ')'];
    }

    protected function _buildSequenceItem(&$grammar, $sequenceItem, $grammarParser, $options)
    {
        $choices = [];
        $sequenceNode = $sequenceItem->getSubnode(1);

        while ($sequenceNode->getDetailType() !== 'last') {
            $choices[] = $this->buildInternalSequence($grammar, $sequenceNode->getSubnode(0), $grammarParser, $options);
            $sequenceNode = $sequenceNode->getSubnode(2);
        }
        $choices[] = $this->buildInternalSequence($grammar, $sequenceNode->getSubnode(0), $grammarParser, $options);
        
        $boolValue = $this->getBoolFromChoices($choices);
        if ($boolValue !== null) {
            return new \ParserGenerator\GrammarNode\BooleanNode($boolValue);
        }

        $node = new \ParserGenerator\GrammarNode\Choice($choices);
        $node->setParser($options['parser']);

        //$grammar[$node->getTmpNodeName()] = $node;

        return $node;
    }

    private function buildInternalSequence(&$grammar, $sequence, $grammarParser, $options)
    {
        $choice = [];

        foreach ($sequence->findAll('sequenceItem') as $sequenceItem) {
            $choice[] = $grammarParser->buildSequenceItem($grammar, $sequenceItem, $options);
        }

        return (count($choice) === 1) ? $choice[0] : $choice;
    }
    
    private function getBoolFromChoices($choices)
    {
        $result = false;
        
        foreach($choices as $sequence) {
            if (!is_array($sequence)) {
                $sequence = [$sequence];
            }
            
            if (count($sequence) === 0) {
                return null;
            }
            $sequenceValue = true;
            foreach($sequence as $item) {
                $itemValue = \ParserGenerator\Util\LeafNodeConverter::getBoolOrNullFromNode($item);
                if ($itemValue === null) {
                    return null;
                } elseif ($itemValue === false) {
                    $sequenceValue = false;
                }
            }
            
            if ($sequenceValue) {
                /*
                 * can't return true as overall result may be null
                 * case: (true | "abc") is not the same as true
                 * start :=> (true | "abc"). will matches string "" and "abc"
                 * while
                 * start :=> true matches only "" 
                 */
                $result = true;
            }
        }
        
        return $result;
    }
}
