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
            $str = (string)$sequenceItem->getSubnode(0)->getValue();
            $regex = \ParserGenerator\Util\Regex::buildRegexFromString($str);
            return new \ParserGenerator\GrammarNode\Regex($regex, $options['ignoreWhitespaces'], $options['caseInsensitive'], $str);
        }

        if (!$options['ignoreWhitespaces']) {
            return new \ParserGenerator\GrammarNode\Text($sequenceItem->getSubnode(0)->getValue());
        }

        return new \ParserGenerator\GrammarNode\TextS($sequenceItem->getSubnode(0)->getValue());
    }
}
