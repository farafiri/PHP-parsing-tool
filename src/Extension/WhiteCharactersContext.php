<?php

namespace ParserGenerator\Extension;

class WhiteCharactersContext extends \ParserGenerator\Extension\SequenceItem
{
    protected function getGrammarGrammarSequence()
    {
        return array(':/(\\\\s|WHITESPACE|\\\\space|SPACE|\\\\n|NEWLINE|\\\\newline|\\\\t|\\\\tab|TAB)/');
    }

    protected function _buildSequenceItem(&$grammar, $sequenceItem, $grammarParser, $options)
    {
        $selector = (string)$sequenceItem;
        $negative = false;

        if (substr($selector, 0, 1) === '!') {
            $selector = substr($selector, 1);
            $negative = true;
        }

        switch ($selector) {
            case '\s':
            case 'WHITESPACE':
                $char = null;
                break;
            case '\n':
            case '\newline':
            case 'NEWLINE':
                $char = "\n";
                break;
            case '\space':
            case 'SPACE':
                $char = ' ';
                break;
            case '\t':
            case '\tab':
            case 'TAB':
                $char = "\t";
                break;
        }

        if ($negative) {
            return new \ParserGenerator\GrammarNode\WhitespaceNegativeContextCheck($char);
        } else {
            return new \ParserGenerator\GrammarNode\WhitespaceContextCheck($char);
        }
    }
}

\ParserGenerator\GrammarParser::$defaultPlugins[] = new WhiteCharactersContext();