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
    abstract function getNode($prams, Parser $parser): GrammarNode\NodeInterface;
    
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
    
    public function getStringFromNode(GrammarNode\NodeInterface $node)
    {
        return $this->_getStringFromNode($node, []);
    }
    
    protected function _getStringFromNode(GrammarNode\NodeInterface $node, $visited)
    {
        if ($node instanceof GrammarNode\Text) {
            return $node->getString();
        }
        
        if ($node instanceof GrammarNode\Decorator) {
            return $this->_getStringFromNode($node->getDecoratedNode(), $visited);
        }
        
        if ($node instanceof GrammarNode\Branch || $node instanceof GrammarNode\Choice || $node instanceof GrammarNode\ParametrizedNode) {
            $hash = spl_object_hash($node);
            if (isset($visited[$hash])) {
                throw new Exception("Cannot use $this<> with nested branches");
            }
            $visited += [$hash => true];
            
            $first = true;
            foreach($node->getNode() as $rule) {
                if ($first === false) {
                    throw new Exception('Cannot get string from branch node with several branches');
                }
                $result = '';
                
                foreach($rule as $subnode) {
                    $result .= $this->_getStringFromNode($subnode, $visited);
                }
                
                $first = false;
            }
            
            return $result;
        }
        
        throw new Exception('Cannot get string from non string node');
    }
}
