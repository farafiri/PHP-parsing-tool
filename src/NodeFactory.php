<?php

namespace ParserGenerator;

use ParserGenerator\Exception;

abstract class NodeFactory
{
    protected $name = 'NodeFactory';
    
    /**
     * @param  GrammarNode\NodeInterface[] $params
     * @param  Parser                 $parser
     */
    abstract function getNode($params, Parser $parser): GrammarNode\NodeInterface;
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setName($name)
    {
        return $this->name = $name;
    }
    
    public function __toString()
    {
        return $this->getName();
    }
    
    protected function getStringFromNode(GrammarNode\NodeInterface $node):string
    {
        return Util\LeafNodeConverter::getStringFromNode($node);
    }
    
    protected function getRegexBodyFromNode(GrammarNode\NodeInterface $node, $ignoreWhitespaces = false):string
    {
        return Util\LeafNodeConverter::getRegexFromNode($node, $ignoreWhitespaces);
    }
    
    protected function getBoolFromNode(GrammarNode\NodeInterface $node):bool
    {
        $bool = Util\LeafNodeConverter::getBoolOrNullFromNode($node);
        
        if ($bool === null) {
            throw new Exception("cannot convert $node to boolean");
        }
        
        return $bool;
    }
}
