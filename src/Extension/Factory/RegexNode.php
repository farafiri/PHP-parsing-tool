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
        $regex = $this->getRegexBodyFromNode($params[0]);
        return new GrammarNode\Regex('/' . $regex . '/');
    }
}
