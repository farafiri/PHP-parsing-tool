<?php

namespace ParserGenerator\Extension\Factory;

use ParserGenerator\NodeFactory;
use ParserGenerator\Parser;
use ParserGenerator\GrammarNode;
use ParserGenerator\Extension\TextNode as Extension;

class TextNode extends NodeFactory 
{
    /**
     * @param GrammarNode\NodeInterface[] $params
     * @param Parser                      $parser
     */
    function getNode($params, Parser $parser): GrammarNode\NodeInterface
    {
        $str = $this->getStringFromNode($params[0]);
        $ignoreWhitespaces = isset($params[1]) ? $this->getBoolFromNode($params[1]) : $parser->options['ignoreWhitespaces'];
        $caseInsensitive = isset($params[2]) ? $this->getBoolFromNode($params[2]) : $parser->options['caseInsensitive'];
        return Extension::getGrammarNode($str, $ignoreWhitespaces, $caseInsensitive);
    }
}
