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
    
    protected function getStringFromNode(GrammarNode\NodeInterface $node)
    {
        return Util\LeafNodeConverter::getStringFromNode($node);
    }
    
    protected function getRegexBodyFromNode(GrammarNode\NodeInterface $node)
    {
        return Util\LeafNodeConverter::getRegexFromNode($node);
    }
}
