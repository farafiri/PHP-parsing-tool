<?php declare(strict_types=1);

namespace ParserGenerator\Extension;

class TextNode extends \ParserGenerator\Extension\SequenceItem
{
    protected function getGrammarGrammarSequence()
    {
        return [':string'];
    }

    protected function _buildSequenceItem(&$grammar, $sequenceItem, $grammarParser, $options)
    {
        if ($options['caseInsensitive']) {
            $regex = \ParserGenerator\RegexUtil::buildRegexFromString((string)$sequenceItem->getSubnode(0)->getValue());
            return new \ParserGenerator\GrammarNode\Regex($regex, $options['ignoreWhitespaces'], $options['caseInsensitive']);
        }

        if (!$options['ignoreWhitespaces']) {
            return new \ParserGenerator\GrammarNode\Text($sequenceItem->getSubnode(0)->getValue());
        }

        return new \ParserGenerator\GrammarNode\TextS($sequenceItem->getSubnode(0)->getValue());
    }
}
