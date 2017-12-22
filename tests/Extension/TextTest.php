<?php

namespace ParserGenerator\Tests\Extension;

use ParserGenerator\Parser;
use ParserGenerator\SyntaxTreeNode\Leaf;
use ParserGenerator\SyntaxTreeNode\Root;
use ParserGenerator\SyntaxTreeNode\Series;

class TextTest extends \PHPUnit_Framework_TestCase
{
    protected function assertObject($a)
    {
        $this->assertTrue(is_object($a));
    }

    public function testBaseText()
    {
        $x = new Parser('start :=> text.', array('ignoreWhitespaces' => true));

        $this->assertEquals(new Root('start', 0, array(
            new Leaf("Lorem ipsum dolor\nsit emet")
        )), $x->parse("Lorem ipsum dolor\nsit emet"));

        $this->assertEquals(new Root('start', 0, array(
            new Leaf("")
        ), ' '), $x->parse(" "));

        $this->assertEquals(new Root('start', 0, array(
            new Leaf("")
        )), $x->parse(""));

        $this->assertEquals(new Root('start', 0, array(
            new Leaf("Lorem ipsum", "\n")
        ), ' '), $x->parse(" Lorem ipsum\n"));

        $x = new Parser('start :=> text.', array('ignoreWhitespaces' => false));

        $this->assertEquals(new Root('start', 0, array(
            new Leaf("")
        )), $x->parse(""));

        $x = new Parser('start :=> text++",".', array('ignoreWhitespaces' => true));

        $this->assertEquals(new Root('start', 0, array(
            new Series('list', 'text', array(
                new Leaf('some text', ' '),
                new Leaf(',', ' '),
                new Leaf('more text')
            ), true)
        )), $x->parse("some text , more text"));

        $parsed = $x->parse("a,a,a,a,a,a,a,a,a,a,a,a,a,a,a,a");

        $this->assertEquals(31, count($parsed->getSubnode(0)->getSubnodes()));
    }
}
