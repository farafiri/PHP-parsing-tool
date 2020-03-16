<?php

namespace  ParserGenerator\Extension\Factory;

use ParserGenerator\NodeFactory;
use ParserGenerator\Parser;
use ParserGenerator\GrammarNode;

class TextNode extends NodeFactory 
{
    /**
     * @param GrammarNode\NodeInterface[] $params
     * @param Parser                      $parser
     */
    function getNode($params, Parser $parser): GrammarNode\NodeInterface
    {
        $str = $this->getStringFromNode($params[0]);
        return new GrammarNode\Text($str);
    }
}
