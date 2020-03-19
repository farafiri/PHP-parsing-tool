<?php

namespace ParserGenerator\GrammarNode;

use ParserGenerator\SyntaxTreeNode\Leaf;

class SeparatedCache extends Decorator
{
    protected $parser;
    
    public function __construct($node, $parser)
    {
        $this->node = $node;
        $this->parser = $parser;
    }
    
    public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
    {
        $cache = $this->parser->cache;
        $cacheStr = $fromIndex . '-' . $this->node->getNodeName() . '-' . implode(',', $restrictedEnd);

        if (isset($cache[$cacheStr])) {
            return $cache[$cacheStr];
        }
        $this->parser->cache = [];
        $result = $this->node->rparse($string, $fromIndex, $restrictedEnd);
        $this->parser->cache = $cache;
        $this->parser->cache[$cacheStr] = $result;
        return $result;
    }
}