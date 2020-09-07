<?php

namespace ParserGenerator\Tests\GrammarNodes;

use ParserGenerator\GrammarNode;
use ParserGenerator\Parser;
use PHPUnit\Framework\TestCase;
use ParserGenerator\SyntaxTreeNode\Leaf;

class BooleanNodeTest extends TestCase
{
    protected function assertObject($a)
    {
        $this->assertTrue(is_object($a));
    }
    
    public function testBase()
    {
        $x = new Parser('start :=> true "a"'
                . '            :=> !true "b"'
                . '            :=> false "c"'
                . '            :=> !false "d".');

        $this->assertObject($x->parse("a"));
        $this->assertFalse($x->parse("b"));
        $this->assertFalse($x->parse("c"));
        $this->assertObject($x->parse("d"));
    }
    
    public function testUseWithParamNode()
    {
        $x = new Parser('start :=> "t" b<true> '
                . '            :=> "f" b<false>.'
                . '      b<bool> :=> bool "1"'
                . '              :=> !bool "0".');

        $this->assertObject($x->parse("t1"));
        $this->assertFalse($x->parse("t0"));
        $this->assertFalse($x->parse("f1"));
        $this->assertObject($x->parse("f0"));
    }
    
    public function getBoolFactory()
    {
        return new class extends \ParserGenerator\NodeFactory {
            function getNode($params, \ParserGenerator\Parser $parser): GrammarNode\NodeInterface {
                $str = $this->getBoolFromNode($params[0]) ? "true" : "false";
                return new GrammarNode\Text($str);
            }
        };
    }
    
    public function testUseWithNodeFactory()
    {
        $x = new Parser('start :=> "t " bool<true> '
                . '            :=> "f " bool<false>.', ['nodes' => ['bool' => $this->getBoolFactory()]]);

        $this->assertObject($x->parse("t true"));
        $this->assertFalse($x->parse("t false"));
        $this->assertFalse($x->parse("f true"));
        $this->assertObject($x->parse("f false"));
    }
    
    public function testLogicOerations()
    {
        $x = new Parser('start :=> "a " bool<!true> '
                . '            :=> "b " bool<!false>'
                . '            :=> "c " bool<(true true)>' //and
                . '            :=> "d " bool<(true false)>' //and
                . '            :=> "e " bool<(false true)>' //and
                . '            :=> "f " bool<(true | false)>' //or
                . '            :=> "g " bool<(false | true)>' //or
                . '            :=> "h " bool<(false | false)>.', ['nodes' => ['bool' => $this->getBoolFactory()]]);

        //a false
        $this->assertObject($x->parse("a false"));
        $this->assertFalse($x->parse("a true"));
        //b true
        $this->assertFalse($x->parse("b false"));
        $this->assertObject($x->parse("b true"));
        //c true
        $this->assertFalse($x->parse("c false"));
        $this->assertObject($x->parse("c true"));
        //d e false
        $this->assertObject($x->parse("e false"));
        $this->assertFalse($x->parse("e true"));
        $this->assertObject($x->parse("d false"));
        $this->assertFalse($x->parse("d true"));
        //f g true
        $this->assertFalse($x->parse("f false"));
        $this->assertObject($x->parse("f true"));
        $this->assertFalse($x->parse("g false"));
        $this->assertObject($x->parse("g true"));
        //h false
        $this->assertObject($x->parse("h false"));
        $this->assertFalse($x->parse("h true"));
    }
    
    public function testLogicNegation()
    {
        $x = new Parser('start :=> "a " bool<!true true> '
                . '            :=> "b " bool<!false true>'
                . '            :=> "c " bool<!true false>'
                . '            :=> "d " bool<!false false>.', ['nodes' => ['bool' => $this->getBoolFactory()]]);

        //b true
        $this->assertFalse($x->parse("b false"));
        $this->assertObject($x->parse("b true"));
        //a c d false
        $this->assertObject($x->parse("a false"));
        $this->assertFalse($x->parse("a true"));
        $this->assertObject($x->parse("c false"));
        $this->assertFalse($x->parse("c true"));
        $this->assertObject($x->parse("d false"));
        $this->assertFalse($x->parse("d true"));
    }
}
