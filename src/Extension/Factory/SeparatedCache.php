<?php

namespace  ParserGenerator\Extension\Factory;

use ParserGenerator\NodeFactory;
use ParserGenerator\Parser;
use ParserGenerator\GrammarNode;
use ParserGenerator\Exception;
use ParserGenerator\GrammarNode\SeparatedCache as CacheNode;

class SeparatedCache extends NodeFactory 
{
    public $cache = [];
    
    /**
     * @param GrammarNode\NodeInterface[] $params
     * @param Parser                      $parser
     */
    function getNode($params, Parser $parser): GrammarNode\NodeInterface
    {
        return new CacheNode($params[0], $parser);
    }
}

