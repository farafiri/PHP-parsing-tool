<?php

namespace ParserGenerator\Tests\Extension;

use ParserGenerator\Parser;
use ParserGenerator\SyntaxTreeNode\Branch;
use ParserGenerator\SyntaxTreeNode\Leaf;
use ParserGenerator\SyntaxTreeNode\Root;
use ParserGenerator\SyntaxTreeNode\Series;
use PHPUnit\Framework\TestCase;

class SeriesTest extends TestCase
{
    protected function assertObject($a): void
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

        $this->assertEquals(new Root('start', 0, [
            new Series('list', 'str', [
                new Branch('str', 0,
                    [new Leaf('a')]),
                new Branch('str', 1,
                    [new Leaf('b')]),
                new Branch('str', 0,
                    [new Leaf('a')]),
                new Branch('str', 2,
                    [new Leaf('c')]),
                new Branch('str', 0,
                    [new Leaf('a')]),
            ], false),
        ]), $x->parse("abaca"));
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

        $this->assertEquals(new Root('start', 0, [
            new Series('list', 'str', [], false),
        ]), $x->parse(''));
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

        $this->assertEquals(new Root('start', 0, [
            new Series('list', 'str', [
                new Branch('str', 0,
                    [new Leaf('a')]),
                new Branch('coma', 0,
                    [new Leaf(',')]),
                new Branch('str', 2,
                    [new Leaf('c')]),
                new Branch('coma', 0,
                    [new Leaf(',')]),
                new Branch('str', 1,
                    [new Leaf('b')]),
            ], true),
        ]), $x->parse("a,c,b"));
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

        $this->assertEquals(new Root('start', 0, [
            new Series('list', '/./', [
                new Leaf('a'),
            ], false),
            new Leaf('bc'),
        ]), $x->parse("abc"));

        $x = new Parser('start :=> /./++  /.*/ .');

        $this->assertEquals(new Root('start', 0, [
            new Series('list', '/./', [
                new Leaf('a'),
                new Leaf('b'),
                new Leaf('c'),
            ], false),
            new Leaf(''),
        ]), $x->parse("abc"));
    }

    public function testSurrounded()
    {
        $x = new Parser('start :=> num+"," /b/.
                         num   :=> /\d+/.');

        $this->assertObject($x->parse('2,3b'));
    }

    public function testSeriesInPegAreAlwaysGreedy()
    {
        $x = new Parser('start :=> "a"+', ['defaultBranchType' => 'PEG']);

        $this->assertObject($x->parse('aaa'));
    }
}
