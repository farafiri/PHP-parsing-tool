<?php

namespace ParserGenerator\Util;

use ParserGenerator\GrammarNode;
use ParserGenerator\Parser;
use ParserGenerator\Exception;
use PHPUnit\Framework\TestCase;
use ParserGenerator\SyntaxTreeNode\Leaf;

class LeafNodeConverter 
{
    public static function getStringFromNode(GrammarNode\NodeInterface $node)
    {
        return static::_getStringFromNode($node, []);
    }
    
    protected static function _getStringFromNode(GrammarNode\NodeInterface $node, $visited)
    {
        if ($node instanceof GrammarNode\Text) {
            return $node->getString();
        }
        
        if ($node instanceof GrammarNode\Regex && $node->getText() !== null) {
            return $node->getText();
        }
        
        if ($node instanceof GrammarNode\Decorator) {
            return static::_getStringFromNode($node->getDecoratedNode(), $visited);
        }
        
        if ($node instanceof GrammarNode\Branch || $node instanceof GrammarNode\Choice || $node instanceof GrammarNode\ParametrizedNode) {
            $hash = spl_object_hash($node);
            if (isset($visited[$hash])) {
                throw new Exception("Cannot use textNode<> with nested branches");
            }
            $visited += [$hash => true];
            
            $first = true;
            foreach($node->getNode() as $rule) {
                if ($first === false) {
                    throw new Exception('Cannot get string from branch node with several branches');
                }
                $result = '';
                
                foreach($rule as $subnode) {
                    $result .= static::_getStringFromNode($subnode, $visited);
                }
                
                $first = false;
            }
            
            return $result;
        }
        
        throw new Exception('Cannot get string from non string node');
    }
    
    public static function getRegexFromNode(GrammarNode\NodeInterface $node)
    {
        return static::_getRegexFromNode($node, []);
    }
    
    protected static function _getRegexFromNode(GrammarNode\NodeInterface $node, $visited)
    {
        if ($node instanceof GrammarNode\Text) {
            return Regex::getRegexBody(Regex::buildRegexFromString($node->getString()));
        }
        
        if ($node instanceof GrammarNode\Regex) {
            return Regex::getRegexBody($node->getRegex());
        }
        
        if ($node instanceof GrammarNode\Series) {
            $mainNodeStr = static::_getRegexFromNode($node->getMainNode(), $visited);
            if ($node->getSeparator()) {
                 $str = $mainNodeStr . '(' . static::_getRegexFromNode($node->getSeparator(), $visited) . $mainNodeStr . ')*';
                 return $node->getFrom0() ? '(' . $str . ')?' : $str;
            } else {
                return '(' . $mainNodeStr . ')' . ($node->getFrom0() ? '*' : '+');
            }
        }
        
        if ($node instanceof GrammarNode\Decorator) {
            return static::_getRegexFromNode($node->getDecoratedNode(), $visited);
        }
        
        if ($node instanceof GrammarNode\Branch || $node instanceof GrammarNode\Choice || $node instanceof GrammarNode\ParametrizedNode) {
            $hash = spl_object_hash($node);
            if (isset($visited[$hash])) {
                var_dump((string) $node);
                throw new Exception("Cannot use regexNode<> with nested branches");
            }
            $visited += [$hash => true];
            
            $branches = [];
            foreach($node->getNode() as $rule) {
                $result = '';
                
                foreach($rule as $subnode) {
                    $result .= '(' . static::_getRegexFromNode($subnode, $visited) . ')';
                }
                
                $branches[] = $result ? ('(' . $result . ')') : '';
            }
            
            return implode('|', $branches);
        }
        
        throw new Exception('Cannot get regex from $node node');
    }
}
