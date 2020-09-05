<?php declare(strict_types=1);

namespace ParserGenerator\Extension;

/**
 * check ExtensionInterface for method descriptions
 */
class Base implements \ParserGenerator\Extension\ExtensionInterface
{
    function extendGrammar($grammarGrammar)
    {
        return $grammarGrammar;
    }

    function modifyBranches($grammar, $parsedGrammar, $grammarParser, $options)
    {
        return $grammar;
    }

    function createGrammarBranch($grammar, $grammarBranch, $grammarParser, $options)
    {
        return $grammar;
    }

    function fillGrammarBranch($grammar, $grammarBranch, $grammarParser, $options)
    {
        return $grammar;
    }

    function buildSequenceItem(&$grammar, $sequenceItem, $grammarParser, $options)
    {
        return false;
    }

    function buildSequence($grammar, $rule, $grammarParser, $options)
    {
        return false;
    }

    function insert($sequence, $search, $value, $offset = 1)
    {
        $index = array_search($search, $sequence) + $offset;
        array_splice($sequence, $index, 0, $value);
        return $sequence;
    }
}