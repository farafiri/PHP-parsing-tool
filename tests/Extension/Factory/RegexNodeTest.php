<?php

namespace ParserGenerator\Tests\Extension\Factory;

use ParserGenerator\GrammarNode;
use ParserGenerator\Parser;
use PHPUnit\Framework\TestCase;
use ParserGenerator\SyntaxTreeNode\Leaf;

class RegexNodeTest extends TestCase
{
    
    protected function assertObject($a)
    {
        $this->assertTrue(is_object($a));
    }
    
    public function testBase()
    {
        $x = new Parser('start :=> "a" regexNode<(b "c")>.'
                . '      b     :=> "b".');
        
        $this->assertObject($x->parse('abc'));
        
        $x = new Parser('start :=> regexNode<(b "c")>.'
                . '      b     :=> /[a-z][0-9]/.');
        
        $this->assertObject($x->parse('a0c'));
        $this->assertObject($x->parse('h4c'));
        $this->assertFalse($x->parse('hhc'));
        
        $x = new Parser('start :=> regexNode<(b "c")>.'
                . '      b     :=> /[a-z]|Z/ z'
                . '            :=> z'
                . '            :=> "X".'
                . '      z     :=> /\d+/.');
        
        $this->assertObject($x->parse('Xc'));
        $this->assertObject($x->parse('a000c'));
        $this->assertObject($x->parse('Z1c'));
        $this->assertFalse($x->parse('ac'));
        $this->assertObject($x->parse('33c'));
        
        $x = new Parser('start :=> regexNode<(b "c")>.'
                . '      b     :=> z++",".'
                . '      z     :=> /\d+/.');
        
        
        $this->assertObject($x->parse('12c'));
        $this->assertObject($x->parse('4,6,7c'));
        $this->assertFalse($x->parse('4,5,c'));
        $this->assertFalse($x->parse('c'));
        $this->assertFalse($x->parse('4,5,'));
        $this->assertFalse($x->parse('4,5'));
        
        $x = new Parser('start :=> regexNode<(b "c")>.'
                . '      b     :=> z**",".'
                . '      z     :=> /\d+/.');
        
        
        $this->assertObject($x->parse('12c'));
        $this->assertObject($x->parse('4,6,7c'));
        $this->assertFalse($x->parse('4,5,c'));
        $this->assertObject($x->parse('c'));
        $this->assertFalse($x->parse('4,5,'));
        $this->assertFalse($x->parse('4,5'));
        
        $x = new Parser('start :=> regexNode<(b "c")>.'
                . '      b     :=> z+.'
                . '      z     :=> /\d+X/.');
        
        
        $this->assertObject($x->parse('12Xc'));
        $this->assertObject($x->parse('4X6X7Xc'));
        $this->assertFalse($x->parse('c'));
        $this->assertFalse($x->parse('4X5X'));
    }
    
    public function testIgnoreWhitespaces()
    {
        $x = new Parser('start :=> "a" regexNode<(b "c")>.'
                . '      b     :=> "b".', ['ignoreWhitespaces' => true]);
        
        $this->assertObject($x->parse('a bc'));
        $this->assertObject($x->parse('abc '));
        $this->assertObject($x->parse('ab c'));
        
        $x = new Parser('start :=> "a" regexNode<(b "c")>.'
                . '      b     :=> "b".');
        
        $this->assertObject($x->parse('abc'));
        $this->assertFalse($x->parse('abc '));
        
        $x = new Parser('start :=> "a" regexNode<(b "c"), false>.'
                . '      b     :=> "b".', ['ignoreWhitespaces' => true]);
        
        $this->assertObject($x->parse('abc'));
        $this->assertObject($x->parse('a bc'));
        $this->assertFalse($x->parse('abc '));
        
        $x = new Parser('start :=> "a" regexNode<(b "c"), true>.'
                . '      b     :=> "b".');
        
        $this->assertObject($x->parse('abc'));
        $this->assertObject($x->parse('ab c'));
        $this->assertFalse($x->parse('a bc'));
    }
}