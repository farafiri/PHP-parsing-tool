<?php declare(strict_types=1);

namespace ParserGenerator\Extension;

use ParserGenerator\Extension\ItemRestrictions\Contain;
use ParserGenerator\Extension\ItemRestrictions\Is;
use ParserGenerator\Extension\ItemRestrictions\ItemRestrictionAnd;
use ParserGenerator\Extension\ItemRestrictions\ItemRestrictionNot;
use ParserGenerator\Extension\ItemRestrictions\ItemRestrictionOr;


class ItemRestrictions extends \ParserGenerator\Extension\SequenceItem
{
    const _NAMESPACE = 'ItemRestrictionsPlugin';

    /**
     * @var callable
     */
    protected $itemBuilderCallback = null;

    public function extendGrammar($grammarGrammar)
    {
        $grammarGrammar[$this->getNS('', false)] = [
            [
                ':sequenceItem',
                $this->getNS('condition'),
            ],
        ];

        $grammarGrammar[$this->getNS('condition', false)] = [
            [$this->getNS('conditionAnd'), 'or', $this->getNS('condition')],
            'last' => [$this->getNS('conditionAnd')],
        ];

        $grammarGrammar[$this->getNS('conditionAnd', false)] = [
            [$this->getNS('simpleCondition'), 'and', $this->getNS('conditionAnd')],
            'last' => [$this->getNS('simpleCondition')],
        ];

        $grammarGrammar[$this->getNS('simpleCondition', false)] = [
            'bracket' => ['(', $this->getNS('condition'), ':comments', ')'],
            'not' => ['not', $this->getNS('simpleCondition')],
            'contain' => ['contain', ':sequenceItem'],
            'is' => ['is', ':sequenceItem'],
        ];

        return parent::extendGrammar($grammarGrammar);
    }

    protected function getNS($node = '', $addColon = true)
    {
        return ($addColon ? ':' : '') . static::_NAMESPACE . ($node ? '_' . $node : '');
    }

    protected function getGrammarGrammarSequence()
    {
        return [$this->getNS('')];
    }

    protected function _buildSequenceItem(&$grammar, $sequenceItem, $grammarParser, $options)
    {
        $this->itemBuilderCallback = function ($sequenceItem) use (&$grammar, $grammarParser, $options) {
            return $grammarParser->buildSequenceItem($grammar, $sequenceItem, $options);
        };

        $grammarNode = $grammarParser->buildSequenceItem($grammar, $sequenceItem->getSubnode(0)->getSubnode(0),
            $options);
        $condition = $this->buildCondition($sequenceItem->getSubnode(0)->getSubnode(1));

        return new \ParserGenerator\GrammarNode\ItemRestrictions($grammarNode, $condition);
    }

    protected function buildCondition($node)
    {
        switch ($node->getType()) {
            case $this->getNS('condition', false):
                if ($node->getDetailType() === 'last') {
                    return $this->buildCondition($node->getSubnode(0));
                } else {
                    return new ItemRestrictionOr([
                        $this->buildCondition($node->getSubnode(0)),
                        $this->buildCondition($node->getSubnode(2)),
                    ]);
                }

            case $this->getNS('conditionAnd', false):
                if ($node->getDetailType() === 'last') {
                    return $this->buildCondition($node->getSubnode(0));
                } else {
                    return new ItemRestrictionAnd([
                        $this->buildCondition($node->getSubnode(0)),
                        $this->buildCondition($node->getSubnode(2)),
                    ]);
                }

            case $this->getNS('simpleCondition', false):
                switch ($node->getDetailType()) {
                    case 'bracket':
                        return $this->buildCondition($node->getSubnode(1));

                    case 'not':
                        return new ItemRestrictionNot($this->buildCondition($node->getSubnode(1)));

                    case 'contain':
                        $itemBuilder = $this->itemBuilderCallback;

                        return new Contain($itemBuilder($node->getSubnode(1)));

                    case 'is':
                        $itemBuilder = $this->itemBuilderCallback;

                        return new Is($itemBuilder($node->getSubnode(1)));
                }
        }
    }
}
