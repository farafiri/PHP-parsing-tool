<?php

namespace ParserGenerator\Tests\Extension;

use ParserGenerator\Parser;
use ParserGenerator\SyntaxTreeNode\Leaf;
use ParserGenerator\SyntaxTreeNode\Root;
use ParserGenerator\SyntaxTreeNode\Series;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    protected function assertObject($a)
    {
        $this->assertTrue(is_object($a));
    }

    public function testBaseText()
    {
        $x = new Parser('start :=> text.', ['ignoreWhitespaces' => true]);

        $this->assertEquals(new Root('start', 0, [
            new Leaf("Lorem ipsum dolor\nsit emet"),
        ]), $x->parse("Lorem ipsum dolor\nsit emet"));

        $this->assertEquals(new Root('start', 0, [
            new Leaf(""),
        ], ' '), $x->parse(" "));

        $this->assertEquals(new Root('start', 0, [
            new Leaf(""),
        ]), $x->parse(""));

        $this->assertEquals(new Root('start', 0, [
            new Leaf("Lorem ipsum", "\n"),
        ], ' '), $x->parse(" Lorem ipsum\n"));

        $x = new Parser('start :=> text.', ['ignoreWhitespaces' => false]);

        $this->assertEquals(new Root('start', 0, [
            new Leaf(""),
        ]), $x->parse(""));

        $x = new Parser('start :=> text++",".', ['ignoreWhitespaces' => true]);

        $this->assertEquals(new Root('start', 0, [
            new Series('list', 'text', [
                new Leaf('some text', ' '),
                new Leaf(',', ' '),
                new Leaf('more text'),
            ], true),
        ]), $x->parse("some text , more text"));

        $parsed = $x->parse("a,a,a,a,a,a,a,a,a,a,a,a,a,a,a,a");

        $this->assertEquals(31, count($parsed->getSubnode(0)->getSubnodes()));
    }
}
