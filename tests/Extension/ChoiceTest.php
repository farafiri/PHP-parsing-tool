<?php

namespace ParserGenerator\Tests\Extension;

use ParserGenerator\Parser;
use ParserGenerator\SyntaxTreeNode\Branch;
use ParserGenerator\SyntaxTreeNode\Leaf;
use ParserGenerator\SyntaxTreeNode\Root;
use ParserGenerator\SyntaxTreeNode\Series;
use PHPUnit\Framework\TestCase;

class ChoiceTest extends TestCase
{
    protected function assertObject($a): void
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

        $this->assertEquals(new Root('start', 0, [
            new Branch('abc', 0,
                [new Leaf('abc')]),
            new Leaf('.'),
        ]), $x->parse("abc."));
    }

    public function testOnAmbigousGrammarChoiceSchouldPickFirstOption()
    {
        $x = new Parser('start :=> ("a" | "b" | "bc") /.*/ .');

        $this->assertEquals(new Root('start', 0, [
            new Leaf('b'),
            new Leaf('cd'),
        ]), $x->parse('bcd'));
    }

    public function testWithSeries()
    {
        $x = new Parser('start :=> (a | b)+ .
                         a     :=> "a".
                         b     :=> "b".');

        $this->assertFalse($x->parse(''));
        $this->assertFalse($x->parse('abc'));

        $this->assertEquals(new Root('start', 0, [
            new Series('list', '', [
                new Branch('a', 0,
                    [new Leaf('a')]),
                new Branch('b', 0,
                    [new Leaf('b')]),
                new Branch('a', 0,
                    [new Leaf('a')]),
                new Branch('b', 0,
                    [new Leaf('b')]),
                new Branch('b', 0, [new Leaf('b')]),
            ], false),
        ]), $x->parse('ababb'));
    }

    public function testSeries()
    {
        $x = new Parser('start :=> ("a" | "b" "c" | "c" ?"d" | "d"++) /.+/.');

        $this->assertEquals(new Root('start', 0, [
            new Leaf('a'),
            new Leaf('a'),
        ]), $x->parse('aa'));

        $this->assertEquals(new Root('start', 0, [
            new Leaf('c'),
            new Leaf('de'),
        ]), $x->parse('cde'));

        $parsed = $x->parse('bce');
        $this->assertTrue((bool)$parsed->getSubnode(0)->getType());
        $parsed->getSubnode(0)->setType('');

        $this->assertEquals(new Root('start', 0, [
            new Branch('', 1, [
                new Leaf('b'),
                new Leaf('c'),
            ]),
            new Leaf('e'),
        ]), $parsed);

        $this->assertEquals(new Root('start', 0, [
            new Series('list', 'd', [
                new Leaf('d'),
                new Leaf('d'),
            ]),
            new Leaf('e'),
        ]), $x->parse('dde'));

        $this->assertEquals(new Root('start', 0, [
            new Series('list', 'd', [
                new Leaf('d'),
            ]),
            new Leaf('e'),
        ]), $x->parse('de'));
    }
}
