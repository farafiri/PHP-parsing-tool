<?php declare(strict_types=1);

namespace ParserGenerator\Extension;

/**
 * check \ParserGenerator\Extension\Base class for default methods implementations
 */
interface ExtensionInterface
{
    /**
     * this function should return modified $grammarGrammar
     * If extension don't need to modify $grammarGrammar then it should retun intact $grammarGrammar param
     * $grammarGrammar is grammar for parsing grammars
     * it is defined as array
     * In \ParserGenerator\GrammarParser::generateNewParser method you can find 
     * $grammarGrammar in its basic form (it means: without modification from extensions)
     * 
     * For example if you would like to support syntax where sequenceItem is followed by ^ (start :=> "bbb"^.)
     * Here is the implementation 
     * 
     * $noWhiteChar = new \ParserGenerator\GrammarNode\WhitespaceNegativeContextCheck(null);
     * $grammarGrammar['sequenceItem']['caret'] = [':sequenceItem', $noWhiteChar, '^'];
     * return $grammarGrammar;
     */
    function extendGrammar($grammarGrammar);

    /**
     * It allows to add,remove and change branches for builded parser
     * it is invoked before node property is populated with real values
     * 
     * @param \ParserGenerator\GrammarNode\NodeInterface[$nodeName] $grammar       grammar being build
     * @param \ParserGenerator\SyntaxTreeNode\Base                  $parsedGrammar 
     * @param \ParserGenerator\GrammarParser                        $grammarParser
     * @param array                                                 $options
     * 
     * @return \ParserGenerator\GrammarNode\NodeInterface[$nodeName] (it schould return $grammar param with applied changes)
     */
    function modifyBranches($grammar, $parsedGrammar, $grammarParser, $options);

    /**
     * Invoked when branch other than standard found (It should create object for new branch)
     * 
     * @param \ParserGenerator\GrammarNode\NodeInterface[$nodeName] $grammar       grammar being build
     * @param \ParserGenerator\SyntaxTreeNode\Base                  $grammarBranch 
     * @param \ParserGenerator\GrammarParser                        $grammarParser
     * @param array                                                 $options
     * 
     * @return \ParserGenerator\GrammarNode\NodeInterface[$nodeName] (it schould return $grammar param with applied changes)
     */
    function createGrammarBranch($grammar, $grammarBranch, $grammarParser, $options);

    /**
     * Invoked when branch other than standard found (It should populate new branch with 'node' values)
     * 
     * @param \ParserGenerator\GrammarNode\NodeInterface[$nodeName] $grammar       grammar being build
     * @param \ParserGenerator\SyntaxTreeNode\Base                  $grammarBranch 
     * @param \ParserGenerator\GrammarParser                        $grammarParser
     * @param array                                                 $options
     * 
     * @return \ParserGenerator\GrammarNode\NodeInterface[$nodeName] (it schould return $grammar param with applied changes)
     */
    function fillGrammarBranch($grammar, $grammarBranch, $grammarParser, $options);

    /**
     * Invoked when sequenceItem other than standard found
     * If this extension can build sequenceItem from provided $sequenceItem then proper
     * \ParserGenerator\GrammarNode\NodeInterface should be returned
     * if provided $sequenceItem is not supported by this extension then this function should return false
     * 
     * It is expected to support types added by extendGrammar method
     * 
     * Example implementation (continuation of example from extendGrammar method)
     * 
     * if($sequenceItem->getDetailType() === 'caret') {
     *     $main = $grammarParser->buildSequenceItem($grammar, $sequenceItem->getSubnode(0), $options);
     *     return new CaretGrammarNode($main);
     * } else {
     *     return false;
     * }
     * 
     * @param \ParserGenerator\GrammarNode\NodeInterface[$nodeName] $grammar       grammar being build
     * @param \ParserGenerator\SyntaxTreeNode\Base                  $sequenceItem 
     * @param \ParserGenerator\GrammarParser                        $grammarParser
     * @param array                                                 $options
     * 
     * @return \ParserGenerator\GrammarNode\NodeInterface|false
     */
    function buildSequenceItem(&$grammar, $sequenceItem, $grammarParser, $options);

    function buildSequence($grammar, $rule, $grammarParser, $options);
}