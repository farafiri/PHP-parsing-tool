<?php declare(strict_types=1);

namespace ParserGenerator\Extension;

class Regex extends \ParserGenerator\Extension\SequenceItem
{
    protected function getGrammarGrammarSequence()
    {
        return array(':/\\/([^*\\\\\\/]|\\\\.)([^\\\\\\/]|\\\\.)*\\/[a-zA-Z]*/');
    }

    protected function _buildSequenceItem(&$grammar, $sequenceItem, $grammarParser, $options)
    {
        return new \ParserGenerator\GrammarNode\Regex((string)$sequenceItem, !empty($options['ignoreWhitespaces']),
            !empty($options['caseInsensitive']));
    }
}
