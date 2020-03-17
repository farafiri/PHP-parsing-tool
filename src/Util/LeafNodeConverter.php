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
            $result = '';
            foreach($node->getNode() as $rule) {
                $branchResult = '';
                
                foreach($rule as $subnode) {
                    $subnodeBool = static::getBoolOrNullFromNode($subnode);
                    if ($subnodeBool === true) {
                        continue 1; //continue with this rule and go to next subnode
                    }
                    if ($subnodeBool === false) {
                        continue 2; //current rule always fail, go to next
                    }
                    $branchResult .= static::_getStringFromNode($subnode, $visited);
                }
                
                if ($first === false) {
                    throw new Exception('Cannot get string from branch node with several branches');
                }
                $first = false;
                $result = $branchResult;
            }
            
            return $result;
        }
        
        throw new Exception('Cannot get string from non string node');
    }
    
    public static function getRegexFromNode(GrammarNode\NodeInterface $node, $ignoreWhitespaces = false)
    {
        return static::_getRegexFromNode($node, $ignoreWhitespaces ? '\s*' : '' ,[]);
    }
    
    protected static function _getRegexFromNode(GrammarNode\NodeInterface $node, $whitespacesRegex, $visited)
    {
        if ($node instanceof GrammarNode\Text) {
            return Regex::getRegexBody(Regex::buildRegexFromString($node->getString())) . $whitespacesRegex;
        }
        
        if ($node instanceof GrammarNode\Regex) {
            return Regex::getRegexBody($node->getRegex()) . $whitespacesRegex;
        }
        
        if ($node instanceof GrammarNode\Series) {
            $mainNodeStr = static::_getRegexFromNode($node->getMainNode(), $whitespacesRegex, $visited);
            if ($node->getSeparator()) {
                 $str = $mainNodeStr . '(' . static::_getRegexFromNode($node->getSeparator(), $whitespacesRegex, $visited) . $mainNodeStr . ')*';
                 return $node->getFrom0() ? '(' . $str . ')?' : $str;
            } else {
                return '(' . $mainNodeStr . ')' . ($node->getFrom0() ? '*' : '+');
            }
        }
        
        if ($node instanceof GrammarNode\Decorator) {
            return static::_getRegexFromNode($node->getDecoratedNode(), $whitespacesRegex, $visited);
        }
        
        if ($node instanceof GrammarNode\Branch || $node instanceof GrammarNode\Choice || $node instanceof GrammarNode\ParametrizedNode) {
            $hash = spl_object_hash($node);
            if (isset($visited[$hash])) {
                throw new Exception("Cannot use regexNode<> with nested branches");
            }
            $visited += [$hash => true];
            
            $branches = [];
            foreach($node->getNode() as $rule) {
                $result = '';
                
                foreach($rule as $subnode) {
                    $subnodeBool = static::getBoolOrNullFromNode($subnode);
                    if ($subnodeBool === true) {
                        continue 1; //continue with this rule and go to next subnode
                    }
                    if ($subnodeBool === false) {
                        continue 2; //current rule always fail, go to next
                    }
                    $result .= '(' . static::_getRegexFromNode($subnode, $whitespacesRegex, $visited) . ')';
                }
                
                $branches[] = $result ? ('(' . $result . ')') : '';
            }
            
            return implode('|', $branches);
        }
        
        throw new Exception('Cannot get regex from $node node');
    }
    
    public static function getBoolOrNullFromNode(GrammarNode\NodeInterface $node)
    {
        if ($node instanceof GrammarNode\Decorator) {
            return static::getBoolOrNullFromNode($node->getDecoratedNode());
        }
        
        if ($node instanceof GrammarNode\BooleanNode) {
            return $node->getValue();
        }
        
        return null;
    }
}
