<?php declare(strict_types=1);

namespace ParserGenerator\Extension;

class Unorder extends \ParserGenerator\Extension\SequenceItem
{
    protected $seqName = 'unorderSequence';

    public function extendGrammar($grammarGrammar)
    {
        $grammarGrammar[$this->seqName] = [
            'nest' => [':/[?*+]?/', ':sequenceItem', ',', (':' . $this->seqName)],
            'last' => [':/[?*+]?/', ':sequenceItem'],
        ];

        return parent::extendGrammar($grammarGrammar);
    }

    protected function getGrammarGrammarSequence()
    {
        return [['unorder(', ':sequenceItem', ',', (':' . $this->seqName), ')']];
    }

    protected function _buildSequenceItem(&$grammar, $sequenceItem, $grammarParser, $options)
    {
        $separator = $this->buildInternalSequence($grammar, $sequenceItem->getSubnode(1), $grammarParser, $options);
        $node = new \ParserGenerator\GrammarNode\Unorder($separator);
        $sequenceNode = $sequenceItem->getSubnode(3);

        while ($sequenceNode) {
            $n = $this->buildInternalSequence($grammar, $sequenceNode->getSubnode(1), $grammarParser, $options);
            $node->addChoice($n, (string)$sequenceNode->getSubnode(0));
            $sequenceNode = ($sequenceNode->getDetailType() == 'last') ? null : $sequenceNode->getSubnode(3);
        }


        $node->setParser($options['parser']);

        $grammar[$node->getTmpNodeName()] = $node;

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
