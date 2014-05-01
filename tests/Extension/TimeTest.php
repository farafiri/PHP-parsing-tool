<?php

namespace ParserGenerator\Tests\Extension;

use ParserGenerator\Parser;

class TimeTest extends \PHPUnit_Framework_TestCase
{
    public function testO()
    {
        $p = new \DateTime('2012-02-31');
        $q = date_parse_from_format('Y-m-d', '2013-03');
        //var_dump($q);
    }

    public function testDataWithStdFormat()
    {
        $x = new Parser('start :=> "q" time(Y-m-d) text.');

        $this->assertEquals(new \DateTime('2024-10-11'), $x->parse('q2024-10-11')->getSubnode(1)->getValue());
        $this->assertEquals(new \DateTime('2004-05-06'), $x->parse('q2004-05-06 more text')->getSubnode(1)->getValue());
    }

    public function testCantParse()
    {
        $x = new Parser('start :=> "q" time(Y-m-d) text.');

        $this->assertFalse($x->parse('q2024-10'));
        $this->assertFalse($x->parse('q2004-05 more text'));
    }

    public function testDataWithOtherFormat()
    {
        $x = new Parser('start :=> "q" time(d.m.Y) text.');

        $this->assertEquals(new \DateTime('2024-10-11'), $x->parse('q11.10.2024')->getSubnode(1)->getValue());
        $this->assertEquals(new \DateTime('2004-05-06'), $x->parse('q06.05.2004 more text')->getSubnode(1)->getValue());
    }

    public function testDataShouldProperlyCaptureWhitespaces()
    {
        $x = new Parser('start :=> time(Y-m-d) text.', array('ignoreWhitespaces' => true));

        $timeNode = $x->parse('2014-03-08  lorem ipsum')->getSubnode(0);
        $this->assertEquals('2014-03-08  ', $timeNode->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_ORIGINAL));
    }
} 