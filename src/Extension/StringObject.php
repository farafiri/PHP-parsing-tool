<?php declare(strict_types=1);

namespace ParserGenerator\Extension;

class StringObject extends \ParserGenerator\Extension\SequenceItem
{
    protected function getGrammarGrammarSequence()
    {
        return [
            ['string'],
            ['string/', ':/(apostrophe|simple|quotation|default)/'],
        ];
    }

    protected function _buildSequenceItem(&$grammar, $sequenceItem, $grammarParser, $options)
    {
        $type = $sequenceItem->getSubnode(1) ? (string)$sequenceItem->getSubnode(1) : 'default';

        switch ($type) {
            case "default":
                return new \ParserGenerator\GrammarNode\PredefinedString($options['ignoreWhitespaces'], ["'", '"']);

            case "apostrophe":
                return new \ParserGenerator\GrammarNode\PredefinedString($options['ignoreWhitespaces'], ["'"]);

            case "quotation":
                return new \ParserGenerator\GrammarNode\PredefinedString($options['ignoreWhitespaces'], ['"']);

            case "simple":
                return new \ParserGenerator\GrammarNode\PredefinedSimpleString($options['ignoreWhitespaces']);
        }
    }
}
