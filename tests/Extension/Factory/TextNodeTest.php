<?php

namespace ParserGenerator\Tests\Extension\Factory;

use ParserGenerator\GrammarNode;
use ParserGenerator\Parser;
use PHPUnit\Framework\TestCase;
use ParserGenerator\SyntaxTreeNode\Leaf;

class TextNodeTest extends TestCase
{
    
    protected function assertObject($a)
    {
        $this->assertTrue(is_object($a));
    }
    
    public function testBase()
    {
        $x = new Parser('start :=> "a" textNode<(b "c")>.'
                . '      b     :=> "b".');
        
        $this->assertObject($x->parse('abc'));
    }
     
    public function testCaseInsensitivity()
    {
        $x = new Parser('start :=> "a" textNode<(b "c")>.'
                . '      b     :=> "b".', ['caseInsensitive' => true]);
        
        $this->assertObject($x->parse('abc'));
        $this->assertObject($x->parse('Abc'));
        $this->assertObject($x->parse('ABc'));
        
        $x = new Parser('start :=> "a" textNode<(b "c")>.'
                . '      b     :=> "b".');
        
        $this->assertObject($x->parse('abc'));
        $this->assertFalse($x->parse('abC'));
        
        $x = new Parser('start :=> "a" textNode<(b "c"), false, false>.'
                . '      b     :=> "b".', ['caseInsensitive' => true]);
        
        $this->assertObject($x->parse('abc'));
        $this->assertObject($x->parse('Abc'));
        $this->assertFalse($x->parse('ABc'));
        
        $x = new Parser('start :=> "a" textNode<(b "c"), false, true>.'
                . '      b     :=> "b".');
        
        $this->assertObject($x->parse('abc'));
        $this->assertObject($x->parse('abC'));
    }
    
    /**
     * @expectedException \ParserGenerator\Exception
     */
    public function testBranchRaisesError()
    {
        $x = new Parser('start :=> "a" textNode<bc>.'
                . '      bc    :=> "b"'
                . '            :=> "c".');
        
        $x->parse("ax");
    }
    
    public function testFalseRemovesRuleInTextNode()
    {
        $x = new Parser('start :=> "a" textNode<bc>.'
                . '      bc    :=> "b"'
                . '            :=> false "c".');
        
        $this->assertObject($x->parse('ab'));
        $this->assertFalse($x->parse('ac'));
    }
    
    /**
     * @expectedException \ParserGenerator\Exception
     */
    public function testTrueDoesNotRemovesRuleInTextNode()
    {
        $x = new Parser('start :=> "a" textNode<bc>.'
                . '      bc    :=> "b"'
                . '            :=> true "c".');
        
        $x->parse("ax");
    }
}
