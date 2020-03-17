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
        return static::getGrammarNode((string)$sequenceItem->getSubnode(0)->getValue(), $options['ignoreWhitespaces'], $options['caseInsensitive']);
    }
    
    public static function getGrammarNode($str, $ignoreWhitespaces, $caseInsensitive)
    {
        if ($caseInsensitive) {
            $regex = \ParserGenerator\Util\Regex::buildRegexFromString($str);
            return new \ParserGenerator\GrammarNode\Regex($regex, $ignoreWhitespaces, $caseInsensitive, $str);
        }

        if ($ignoreWhitespaces) {
            return new \ParserGenerator\GrammarNode\TextS($str);
        }

        return new \ParserGenerator\GrammarNode\Text($str);
    }
}
