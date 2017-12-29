<?php declare(strict_types=1);

namespace ParserGenerator\Extension;

class Regex extends \ParserGenerator\Extension\SequenceItem
{
    protected function getGrammarGrammarSequence()
    {
        return [':/\\/([^*\\\\\\/]|\\\\.)([^\\\\\\/]|\\\\.)*\\/[a-zA-Z]*/'];
    }

    protected function _buildSequenceItem(&$grammar, $sequenceItem, $grammarParser, $options)
    {
        return new \ParserGenerator\GrammarNode\Regex((string)$sequenceItem, $options['ignoreWhitespaces'],
            $options['caseInsensitive']);
    }
}
