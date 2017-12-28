<?php

namespace ParserGenerator\Tests\Extension;

use ParserGenerator\Parser;
use PHPUnit\Framework\TestCase;

class ChoiceTest extends TestCase
{
    protected function assertObject($a)
    {
        $this->assertTrue(is_object($a));
    }

    public function testBase()
    {
        $x = new Parser('start :=> (abc | "z" | /[qwe]/) "." .
                         abc   :=> "abc".');

        $this->assertFalse($x->parse('ax.'));
        $this->assertFalse($x->parse('a.'));
        $this->assertFalse($x->parse('.'));
        $this->assertFalse($x->parse(''));
        $this->assertObject($x->parse('abc.'));
        $this->assertObject($x->parse('z.'));
        $this->assertObject($x->parse('w.'));
    }

    public function testChoiceSchouldNotCreateNewLevelInResult()
    {
        $x = new Parser('start :=> (abc | "z" | /[qwe]/) "." .
                         abc   :=> "abc".');

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, array(
            new \ParserGenerator\SyntaxTreeNode\Branch('abc', 0,
                array(new \ParserGenerator\SyntaxTreeNode\Leaf('abc'))),
            new \ParserGenerator\SyntaxTreeNode\Leaf('.'),
        )), $x->parse("abc."));
    }

    public function testOnAmbigousGrammarChoiceSchouldPickFirstOption()
    {
        $x = new Parser('start :=> ("a" | "b" | "bc") /.*/ .');

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, array(
            new \ParserGenerator\SyntaxTreeNode\Leaf('b'),
            new \ParserGenerator\SyntaxTreeNode\Leaf('cd')
        )), $x->parse('bcd'));
    }

    public function testWithSeries()
    {
        $x = new Parser('start :=> (a | b)+ .
                         a     :=> "a".
                         b     :=> "b".');

        $this->assertFalse($x->parse(''));
        $this->assertFalse($x->parse('abc'));

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, array(
            new \ParserGenerator\SyntaxTreeNode\Series('list', '', array(
                new \ParserGenerator\SyntaxTreeNode\Branch('a', 0,
                    array(new \ParserGenerator\SyntaxTreeNode\Leaf('a'))),
                new \ParserGenerator\SyntaxTreeNode\Branch('b', 0,
                    array(new \ParserGenerator\SyntaxTreeNode\Leaf('b'))),
                new \ParserGenerator\SyntaxTreeNode\Branch('a', 0,
                    array(new \ParserGenerator\SyntaxTreeNode\Leaf('a'))),
                new \ParserGenerator\SyntaxTreeNode\Branch('b', 0,
                    array(new \ParserGenerator\SyntaxTreeNode\Leaf('b'))),
                new \ParserGenerator\SyntaxTreeNode\Branch('b', 0, array(new \ParserGenerator\SyntaxTreeNode\Leaf('b')))
            ), false)
        )), $x->parse('ababb'));
    }

    public function testSeries()
    {
        $x = new Parser('start :=> ("a" | "b" "c" | "c" ?"d" | "d"++) /.+/.');

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, array(
            new \ParserGenerator\SyntaxTreeNode\Leaf('a'),
            new \ParserGenerator\SyntaxTreeNode\Leaf('a')
        )), $x->parse('aa'));

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, array(
            new \ParserGenerator\SyntaxTreeNode\Leaf('c'),
            new \ParserGenerator\SyntaxTreeNode\Leaf('de')
        )), $x->parse('cde'));

        $parsed = $x->parse('bce');
        $this->assertTrue((bool)$parsed->getSubnode(0)->getType());
        $parsed->getSubnode(0)->setType('');

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, array(
            new \ParserGenerator\SyntaxTreeNode\Branch('', 1, array(
                new \ParserGenerator\SyntaxTreeNode\Leaf('b'),
                new \ParserGenerator\SyntaxTreeNode\Leaf('c')
            )),
            new \ParserGenerator\SyntaxTreeNode\Leaf('e')
        )), $parsed);

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, array(
            new \ParserGenerator\SyntaxTreeNode\Series('list', 'd', array(
                new \ParserGenerator\SyntaxTreeNode\Leaf('d'),
                new \ParserGenerator\SyntaxTreeNode\Leaf('d')
            )),
            new \ParserGenerator\SyntaxTreeNode\Leaf('e')
        )), $x->parse('dde'));

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, array(
            new \ParserGenerator\SyntaxTreeNode\Series('list', 'd', array(
                new \ParserGenerator\SyntaxTreeNode\Leaf('d')
            )),
            new \ParserGenerator\SyntaxTreeNode\Leaf('e')
        )), $x->parse('de'));
    }
}
