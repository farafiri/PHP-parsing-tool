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
        $choices[] = $this->buildInternalSequence($grammar, $sequenceNode->getSubnode(0), $grammarParser, $options);;

        $node = new \ParserGenerator\GrammarNode\Choice($choices);
        if (isset($options['parser'])) {
            $node->setParser($options['parser']);
        }

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
}
