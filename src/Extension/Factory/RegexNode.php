<?php

namespace ParserGenerator\Extension\Factory;

use ParserGenerator\NodeFactory;
use ParserGenerator\Parser;
use ParserGenerator\GrammarNode;

class RegexNode extends NodeFactory 
{
    /**
     * @param GrammarNode\NodeInterface[] $params
     * @param Parser                      $parser
     */
    function getNode($params, Parser $parser): GrammarNode\NodeInterface
    {
        $ignoreWhitespaces = isset($params[1]) ? $this->getBoolFromNode($params[1]) : $parser->options['ignoreWhitespaces'];
        $caseInsensitive = isset($params[2]) ? $this->getBoolFromNode($params[2]) : $parser->options['caseInsensitive'];
        $regex = $this->getRegexBodyFromNode($params[0], $ignoreWhitespaces);
        return new GrammarNode\Regex('/' . $regex . '/', $ignoreWhitespaces, $caseInsensitive);
    }
}
