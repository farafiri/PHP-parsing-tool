<?php

use ParserGenerator\Parser;

class GrammarNodeBranchTest extends PHPUnit_Framework_TestCase
{
    public function testCanBeEmpty_Basic()
    {
        $x = new Parser(array(
            'start' => array(array('asd')),
            'x1' => array(array('')),
            'x2' => array(array('asdf', '', '')),
            'x3' => array(array('', 'asdf', '')),
            'x4' => array(array('', '', 'asdf')),
            'x5' => array(array('asdf'), array('cdge'), array('werwer')),
            'x6' => array(array('asdf'), array(''), array('werwer')),
            'x7' => array(array('asdf'), array('wer'), array('')),
            'x8' => array(array(''), array('sdfs'), array('werwer'))
        ));

        $this->assertFalse($x->grammar['start']->canBeEmpty());
        $this->assertTrue($x->grammar['x1']->canBeEmpty());
        $this->assertFalse($x->grammar['x2']->canBeEmpty());
        $this->assertFalse($x->grammar['x3']->canBeEmpty());
        $this->assertFalse($x->grammar['x4']->canBeEmpty());
        $this->assertFalse($x->grammar['x5']->canBeEmpty());
        $this->assertTrue($x->grammar['x6']->canBeEmpty());
        $this->assertTrue($x->grammar['x7']->canBeEmpty());
        $this->assertTrue($x->grammar['x8']->canBeEmpty());
    }

    public function testCanBeEmpty_OtherBranchNodeReferences()
    {
        $x = new Parser(array(
            'start' => array(array('asd'), array(':x', ':y'), array(':x', '')),
            'x' => array('x', 'r', ':h'),
            'h' => array('x', ''),
            'y' => array(array(':h', ':h', 'x'), array('as', ':x'))
        ));

        $this->assertTrue($x->grammar['h']->canBeEmpty());
        $this->assertTrue($x->grammar['x']->canBeEmpty());
        $this->assertFalse($x->grammar['y']->canBeEmpty());
        $this->assertTrue($x->grammar['start']->canBeEmpty());
    }

    public function testCanBeEmpty_OtherBranchNodeCircuralReferences()
    {
        $x = new Parser(array(
            'x' => array(':y'),
            'y' => array(':x', 'asad')
        ));

        $this->assertFalse($x->grammar['x']->canBeEmpty());
        $this->assertFalse($x->grammar['y']->canBeEmpty());

        $x = new Parser(array(
            'x' => array(':y'),
            'y' => array(':x', '')
        ));

        $this->assertTrue($x->grammar['x']->canBeEmpty());
        $this->assertTrue($x->grammar['y']->canBeEmpty());
    }

    public function testStartChars_Basic()
    {
        $x = new Parser(array(
            'l' => array(array(':y', ':x')),
            'k' => array(':x', ':y'),
            'm' => array(array('a', 'b'), array('c', ':x', ':y')),
            'n' => array(array('', 'a'), array('', ':x')),
            'z' => array(':x', 'a', 'b'),
            'y' => array('q', 'w', ''),
            'x' => array('x', 'y')
        ));

        $this->assertEquals(array('x' => true, 'y' => true), $x->grammar['x']->startChars());
        $this->assertEquals(array('q' => true, 'w' => true), $x->grammar['y']->startChars());
        $this->assertEquals(array('x' => true, 'y' => true, 'a' => true, 'b' => true), $x->grammar['z']->startChars());
        $this->assertEquals(array('a' => true, 'x' => true, 'y' => true), $x->grammar['n']->startChars());
        $this->assertEquals(array('a' => true, 'c' => true), $x->grammar['m']->startChars());
        $this->assertEquals(array('x' => true, 'y' => true, 'q' => true, 'w' => true), $x->grammar['k']->startChars());
        $this->assertEquals(array('q' => true, 'w' => true, 'x' => true, 'y' => true), $x->grammar['l']->startChars());
    }

    public function testStartChars_CircularReferences()
    {
        $x = new Parser(array(
            'x' => array(array(':x', 'y'), 'b')
        ));

        $this->assertEquals(array('b' => true), $x->grammar['x']->startChars());

        $x = new Parser(array(
            'y' => array(array(':x', 'b', ':x', 'c'), 'd'),
            'x' => array(array(':x', 'a'), '')
        ));

        $this->assertEquals(array('a' => true), $x->grammar['x']->startChars());
        $this->assertEquals(array('b' => true, 'd' => true, 'a' => true), $x->grammar['y']->startChars());

        $x = new Parser(array(
            'y' => array(array(':x', 'b', ':x'), 'd'),
            'x' => array(array(':y', 'c', ':x'), 'a')
        ));

        $this->assertEquals(array('d' => true, 'a' => true), $x->grammar['y']->startChars());
        $this->assertEquals(array('d' => true, 'a' => true), $x->grammar['x']->startChars());

        $x = new Parser(array(
            'y' => array(array(':x', 'b', ':x'), ''),
            'x' => array(array(':y', 'c', ':x'), 'a')
        ));

        $this->assertEquals(array('a' => true, 'c' => true), $x->grammar['y']->startChars());
        $this->assertEquals(array('a' => true, 'c' => true), $x->grammar['x']->startChars());
    }
}