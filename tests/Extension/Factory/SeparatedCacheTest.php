<?php

namespace ParserGenerator\Tests\Extension\Factory;

use ParserGenerator\GrammarNode;
use ParserGenerator\Parser;
use PHPUnit\Framework\TestCase;
use ParserGenerator\SyntaxTreeNode\Leaf;

class SeparatedCacheTest extends TestCase
{
    
    protected function assertObject($a)
    {
        $this->assertTrue(is_object($a));
    }
    
    public function testBase()
    {
        $x = new Parser('start :=> separatedCache<b++",">++";".'
                . '      b     :=> "b".');
        
        $this->assertObject($x->parse('b,b,b;b,b,b;b,b;b;b,b,b;b'));
        $this->assertFalse($x->parse('b,b,b,;b,b'));
        $this->assertFalse($x->parse('b,b,b;b,b;'));
    }
}

