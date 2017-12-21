<?php

namespace ParserGenerator\Tests\Extension;

use ParserGenerator\Parser;

class SeriesTest extends \PHPUnit_Framework_TestCase
{
    protected function assertObject($a)
    {
        $this->assertTrue(is_object($a));
    }

    public function testPWithoutSeparator()
    {
        $x = new Parser('start :=> str+.
	                 str   :=> "a"
					       :=> "b"
						   :=> "c".');


        $this->assertFalse($x->parse('ax'));
        $this->assertObject($x->parse('a'));
        $this->assertObject($x->parse('b'));
        $this->assertObject($x->parse('abacab'));
        $this->assertFalse($x->parse(''));

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, array(
            new \ParserGenerator\SyntaxTreeNode\Series('list', 'str', array(
                new \ParserGenerator\SyntaxTreeNode\Branch('str', 0,
                    array(new \ParserGenerator\SyntaxTreeNode\Leaf('a'))),
                new \ParserGenerator\SyntaxTreeNode\Branch('str', 1,
                    array(new \ParserGenerator\SyntaxTreeNode\Leaf('b'))),
                new \ParserGenerator\SyntaxTreeNode\Branch('str', 0,
                    array(new \ParserGenerator\SyntaxTreeNode\Leaf('a'))),
                new \ParserGenerator\SyntaxTreeNode\Branch('str', 2,
                    array(new \ParserGenerator\SyntaxTreeNode\Leaf('c'))),
                new \ParserGenerator\SyntaxTreeNode\Branch('str', 0,
                    array(new \ParserGenerator\SyntaxTreeNode\Leaf('a'))),
            ), false)
        )), $x->parse("abaca"));
    }

    public function testMWithoutSeparator()
    {
        $x = new Parser('start :=> str*.
	                 str   :=> "a"
					       :=> "b"
						   :=> "c".');


        $this->assertFalse($x->parse('ax'));
        $this->assertObject($x->parse('a'));
        $this->assertObject($x->parse('b'));
        $this->assertObject($x->parse('abacab'));

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, array(
            new \ParserGenerator\SyntaxTreeNode\Series('list', 'str', array(), false)
        )), $x->parse(''));
    }

    public function testWithSeparator()
    {
        $x = new Parser('start :=> str+coma.
	                 coma  :=> ",".
	                 str   :=> "a"
					       :=> "b"
						   :=> "c".');

        $this->assertFalse($x->parse('a,'));
        $this->assertObject($x->parse('a'));
        $this->assertObject($x->parse('b,a'));
        $this->assertFalse($x->parse(''));

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, array(
            new \ParserGenerator\SyntaxTreeNode\Series('list', 'str', array(
                new \ParserGenerator\SyntaxTreeNode\Branch('str', 0,
                    array(new \ParserGenerator\SyntaxTreeNode\Leaf('a'))),
                new \ParserGenerator\SyntaxTreeNode\Branch('coma', 0,
                    array(new \ParserGenerator\SyntaxTreeNode\Leaf(','))),
                new \ParserGenerator\SyntaxTreeNode\Branch('str', 2,
                    array(new \ParserGenerator\SyntaxTreeNode\Leaf('c'))),
                new \ParserGenerator\SyntaxTreeNode\Branch('coma', 0,
                    array(new \ParserGenerator\SyntaxTreeNode\Leaf(','))),
                new \ParserGenerator\SyntaxTreeNode\Branch('str', 1,
                    array(new \ParserGenerator\SyntaxTreeNode\Leaf('b'))),
            ), true)
        )), $x->parse("a,c,b"));
    }

    public function testWithVariousTypes()
    {
        $x = new Parser('start :=> /[abc]/+",".');

        $this->assertFalse($x->parse('a,'));
        $this->assertObject($x->parse('a'));
        $this->assertObject($x->parse('b,c'));
        $this->assertFalse($x->parse(''));

        $x = new Parser('start :=> /[abc]/*",".');

        $this->assertFalse($x->parse('a,'));
        $this->assertObject($x->parse('a'));
        $this->assertObject($x->parse('b,c'));
        $this->assertObject($x->parse(''));

        $x = new Parser('start :=> 1..100+",".');

        $this->assertObject($x->parse('12'));
        $this->assertObject($x->parse('1,42,6'));
        $this->assertFalse($x->parse('a'));
        $this->assertFalse($x->parse(''));
    }

    public function testSpacing()
    {
        /* this parses letters separated by coma
         * $x = new Parser('start :=> /[abc]/+",".');
         *
         * but this should parse letters ended with coma
         * $x = new Parser('start :=> /[abc]/+ ",".');
         */

        $x = new Parser('start :=> /[abc]/+ ",".');

        $this->assertObject($x->parse('a,'));
        $this->assertFalse($x->parse('a'));
        $this->assertFalse($x->parse('b,c'));
        $this->assertObject($x->parse('abcc,'));
    }

    public function testGreed()
    {
        // by default series are not greedy but if we repeat series sign
        // series become greedy

        $x = new Parser('start :=> /./+  /.*/ .');

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, array(
            new \ParserGenerator\SyntaxTreeNode\Series('list', '/./', array(
                new \ParserGenerator\SyntaxTreeNode\Leaf('a')
            ), false),
            new \ParserGenerator\SyntaxTreeNode\Leaf('bc')
        )), $x->parse("abc"));

        $x = new Parser('start :=> /./++  /.*/ .');

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, array(
            new \ParserGenerator\SyntaxTreeNode\Series('list', '/./', array(
                new \ParserGenerator\SyntaxTreeNode\Leaf('a'),
                new \ParserGenerator\SyntaxTreeNode\Leaf('b'),
                new \ParserGenerator\SyntaxTreeNode\Leaf('c')
            ), false),
            new \ParserGenerator\SyntaxTreeNode\Leaf('')
        )), $x->parse("abc"));
    }

    public function testSurrounded()
    {
        $x = new Parser('start :=> num+"," /b/.
	                 num   :=> /\d+/.');

        $this->assertObject($x->parse('2,3b'));
    }

    public function testSeriesInPegAreAlwaysGreedy()
    {
        $x = new Parser('start :=> "a"+', array('defaultBranchType' => 'PEG'));

        $this->assertObject($x->parse('aaa'));
    }
}
