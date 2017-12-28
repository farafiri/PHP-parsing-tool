<?php

namespace ParserGenerator\Tests\Extension;

use ParserGenerator\Parser;
use PHPUnit\Framework\TestCase;

class LookaheadTest extends TestCase
{
    protected function assertObject($a)
    {
        $this->assertTrue(is_object($a));
    }

    public function testSimplePositive()
    {
        $x = new Parser('start :=> ?"abc" /.+/ .');

        $this->assertFalse($x->parse('ax'));
        $this->assertFalse($x->parse('ab'));
        $this->assertFalse($x->parse('bcde'));
        $this->assertFalse($x->parse('cba'));
        $this->assertObject($x->parse('abc'));
        $this->assertObject($x->parse('abcbn'));
    }

    public function testSimpleNegative()
    {
        $x = new Parser('start :=> !"abc" /.+/ .');

        $this->assertObject($x->parse('ax'));
        $this->assertObject($x->parse('ab'));
        $this->assertObject($x->parse('bcde'));
        $this->assertObject($x->parse('cba'));
        $this->assertFalse($x->parse('abc'));
        $this->assertFalse($x->parse('abcbn'));
    }

    public function testSimplePositiveAfter()
    {
        $x = new Parser('start :=> x /.*/ .
                         x     :=> /.{3}/  !"a" .');

        $this->assertObject($x->parse('axcd'));
        $this->assertObject($x->parse('aaadaa'));
        $this->assertObject($x->parse('abc'));
        $this->assertFalse($x->parse('abca'));
        $this->assertFalse($x->parse('abcabn'));
    }

    public function testWithRegex()
    {
        $x = new Parser('start :=> !/.{3}/ /.+/ .');

        $this->assertObject($x->parse('ax'));
        $this->assertObject($x->parse('ab'));
        $this->assertFalse($x->parse('bcde'));
        $this->assertFalse($x->parse('abcde'));
        $this->assertFalse($x->parse('cba'));
    }

    public function testWithChoice()
    {
        $x = new Parser('start :=> ?("abc" | "cba") /.+/ .');

        $this->assertFalse($x->parse('ax'));
        $this->assertFalse($x->parse('ab'));
        $this->assertFalse($x->parse('bcde'));
        $this->assertObject($x->parse('abcde'));
        $this->assertObject($x->parse('cbax'));
    }

    public function testLookaroundDontProduceToken()
    {
        $x = new Parser('start :=> ?/[abc]/ /.+/ .');

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, [
            new \ParserGenerator\SyntaxTreeNode\Leaf('abc'),
        ]), $x->parse("abc"));

        $x = new Parser('start :=> ?abc /.+/ .
                         abc   :=> a b c.
                         a     :=> "a".
                         b     :=> "b".
                         c     :=> "c".');

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, [
            new \ParserGenerator\SyntaxTreeNode\Leaf('abc'),
        ]), $x->parse("abc"));
    }

    public function testInsideChoice()
    {
        $x = new Parser('start :=> (?"abc" /./| "ab")  /.*/ .');

        $this->assertFalse($x->parse('acd'));
        $this->assertFalse($x->parse(''));

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, [
            new \ParserGenerator\SyntaxTreeNode\Leaf('ab'),
            new \ParserGenerator\SyntaxTreeNode\Leaf('de'),
        ]), $x->parse("abde"));

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, [
            new \ParserGenerator\SyntaxTreeNode\Leaf('a'),
            new \ParserGenerator\SyntaxTreeNode\Leaf('bce'),
        ]), $x->parse("abce"));

        $x = new Parser('start :=> (?"abc" | "bc")  /.*/ .');

        $this->assertEquals(new \ParserGenerator\SyntaxTreeNode\Root('start', 0, [
            new \ParserGenerator\SyntaxTreeNode\Leaf(''),
            new \ParserGenerator\SyntaxTreeNode\Leaf('abce'),
        ]), $x->parse("abce"));
    }

    public function testAnBnCnGrammar()
    {
        $x = new Parser('start :=> ?(A "c") "a"++ B.
                         A     :=> "a" A? "b".
                         B     :=> "b" B? "c".');

        $this->assertObject($x->parse('abc'));
        $this->assertObject($x->parse('aabbcc'));
        $this->assertObject($x->parse('aaabbbccc'));

        $this->assertFalse($x->parse('aabb'));
        $this->assertFalse($x->parse('aacc'));
        $this->assertFalse($x->parse('bbcc'));

        $this->assertFalse($x->parse('aabbc'));
        $this->assertFalse($x->parse('aabcc'));
        $this->assertFalse($x->parse('abbcc'));

        $this->assertFalse($x->parse('aabbccc'));
        $this->assertFalse($x->parse('aabbbcc'));
        $this->assertFalse($x->parse('aaabbcc'));
    }

    public function testBugNoBacktracking()
    {
        $x = new Parser('start :=> text ?"c" text.');

        $this->assertObject($x->parse('abcd'));
    }

    public function testErrorTrack()
    {
        $x = new Parser('start :=> "q" ?/.b/ "a".');

        $this->assertFalse($x->parse('qa'));
        $e = $x->getError();
        $this->assertEquals(1, $e['index']);
        $this->assertEquals('?/.b/ "a"', implode(' ', $e['expected']));

        $this->assertFalse($x->parse('qcb'));
        $e = $x->getError();
        $this->assertEquals(1, $e['index']);
        $this->assertEquals('?/.b/ "a"', implode(' ', $e['expected']));

        $x = new Parser('start :=> "q" !/[ab]/ /[bc]/.');

        $this->assertFalse($x->parse('qa'));
        $e = $x->getError();
        $this->assertEquals(1, $e['index']);
        $this->assertEquals('!/[ab]/ /[bc]/', implode(' ', $e['expected']));

        $this->assertFalse($x->parse('qb'));
        $e = $x->getError();
        $this->assertEquals(1, $e['index']);
        $this->assertEquals('!/[ab]/ /[bc]/', implode(' ', $e['expected']));
    }
}
