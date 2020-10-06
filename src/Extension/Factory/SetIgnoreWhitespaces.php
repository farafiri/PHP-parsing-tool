<?php

namespace ParserGenerator\Extension\Factory;

use ParserGenerator\NodeFactory;
use ParserGenerator\Parser;
use ParserGenerator\GrammarNode;
use ParserGenerator\Extension\TextNode;
use ParserGenerator\Util\LeafNodeConverter;

class SetIgnoreWhitespaces extends NodeFactory {
    
    /**
     * @param GrammarNode\NodeInterface[] $params
     * @param Parser                      $parser
     */
    function getNode($params, Parser $parser): GrammarNode\NodeInterface
    {
        $node              = $params[0];
        $ignoreWhitespaces = $params[1];
        $bool              = LeafNodeConverter::getBoolOrNullFromNode($ignoreWhitespaces);
        $regex             = $bool === null ? $this->getRegexBodyFromNode($ignoreWhitespaces, false) : GrammarNode\WhiteCharsHelper::getRegex($bool);
        
        $callback = function($node) use ($regex, &$callback) {
            if ($node instanceof GrammarNode\LeafInterface) {
                $text = TextNode::getText($node);
                if ($text !== null) {
                    $caseInsensitive = $node instanceof GrammarNode\Regex;
                    return TextNode::getGrammarNode($text, $regex, $caseInsensitive);
                } elseif (method_exists($node, 'setEatWhiteChars')) {
                    $newNode = clone $node;
                    $newNode->setEatWhiteChars($regex);
                    return $newNode;
                } else {
                    return $node;
                }
            }
            
            return true;
        };
        
        $copy = \ParserGenerator\GrammarNodeCopier::copy($node, $callback, true);
        return new GrammarNode\OverrideAfterContent($copy, $parser->options['ignoreWhitespaces']);
    }
}
