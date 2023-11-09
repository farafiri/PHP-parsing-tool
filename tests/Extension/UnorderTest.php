<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 09.08.15
 * Time: 20:23
 */

namespace ParserGenerator\Tests\Extension;

use ParserGenerator\Parser;
use PHPUnit\Framework\TestCase;

class UnorderTest extends TestCase
{
    protected function assertObject($a): void
    {
        $this->assertTrue(is_object($a));
    }

    public function testBase()
    {
        $x = new Parser('start :=> unorder("", "a", "b").');

        $this->assertObject($x->parse('ab'));
        $this->assertObject($x->parse('ba'));
        $this->assertFalse($x->parse('a'));
        $this->assertFalse($x->parse('b'));
        $this->assertFalse($x->parse(''));
        $this->assertFalse($x->parse('aba'));
        $this->assertFalse($x->parse('baba'));
        $this->assertFalse($x->parse('aa'));
    }

    public function testBaseWithThreeNodes()
    {
        $x = new Parser('start :=> unorder("", "a", "b", "c").');

        $this->assertFalse($x->parse('ab'));
        $this->assertObject($x->parse('abc'));
        $this->assertObject($x->parse('acb'));
        $this->assertObject($x->parse('cab'));
        $this->assertObject($x->parse('cba'));
        $this->assertFalse($x->parse('aba'));
        $this->assertFalse($x->parse('ac'));
    }

    public function testSeparator()
    {
        $x = new Parser('start :=> unorder(",", "a", "b", "c").');

        $this->assertFalse($x->parse('a,b'));
        $this->assertObject($x->parse('a,b,c'));
        $this->assertObject($x->parse('c,b,a'));

        $this->assertFalse($x->parse('ab'));
        $this->assertFalse($x->parse('abc'));
        $this->assertFalse($x->parse('cba'));
    }

    public function testFallback()
    {
        $x = new Parser('start :=> unorder("", ("abc" | "ab" | "a"), "b", "c").');

        $this->assertObject($x->parse('abc'));
        $this->assertObject($x->parse('abcbc'));
        $this->assertObject($x->parse('abcb'));
        $this->assertFalse($x->parse('abcc'));
        $this->assertObject($x->parse('babc'));
        $this->assertFalse($x->parse('cabc'));
    }

    public function testNonTrivialExample()
    {
        $x = new Parser('start :=> unorder("", ("a"|"b"|"c"), ("b"|"c"|"d"), ("c"|"a"), ("b"|"d")).');

        $this->assertObject($x->parse('caab'));
        $this->assertObject($x->parse('bcca'));
        $this->assertObject($x->parse('abbc'));
        $this->assertFalse($x->parse('accc'));
        $this->assertFalse($x->parse('ccca'));
    }

    public function testQModifier()
    {
        $x = new Parser('start :=> unorder("", ?"a", ?"b", ?"c").');

        $this->assertObject($x->parse('abc'));
        $this->assertObject($x->parse('cba'));
        $this->assertObject($x->parse('a'));
        $this->assertObject($x->parse('c'));
        $this->assertObject($x->parse('bc'));
        $this->assertFalse($x->parse(''));
        $this->assertFalse($x->parse('aa'));
        $this->assertFalse($x->parse('aabbcc'));
        $this->assertFalse($x->parse('caa'));
    }

    public function testAModifier()
    {
        $x = new Parser('start :=> unorder("", +"a", +"b", +"c").');

        $this->assertObject($x->parse('abc'));
        $this->assertObject($x->parse('cba'));
        $this->assertFalse($x->parse('a'));
        $this->assertFalse($x->parse('c'));
        $this->assertFalse($x->parse('bc'));
        $this->assertFalse($x->parse(''));
        $this->assertFalse($x->parse('aa'));
        $this->assertObject($x->parse('aabbcc'));
        $this->assertFalse($x->parse('caa'));
        $this->assertObject($x->parse('aacaaabb'));
    }

    public function testMModifier()
    {
        $x = new Parser('start :=> unorder("", *"a", *"b", *"c").');

        $this->assertObject($x->parse('abc'));
        $this->assertObject($x->parse('cba'));
        $this->assertObject($x->parse('a'));
        $this->assertObject($x->parse('c'));
        $this->assertObject($x->parse('bc'));
        $this->assertFalse($x->parse(''));
        $this->assertObject($x->parse('aa'));
        $this->assertObject($x->parse('aabbcc'));
        $this->assertObject($x->parse('caa'));
        $this->assertObject($x->parse('aacaaabb'));
    }

    public function testMixedModifier()
    {
        $x = new Parser('start :=> unorder("", *"a", "b", ?"c").');

        $this->assertObject($x->parse('abc'));
        $this->assertObject($x->parse('b'));
        $this->assertObject($x->parse('cb'));
        $this->assertFalse($x->parse('aac'));
        $this->assertObject($x->parse('aaba'));
        $this->assertFalse($x->parse('aabba'));
        $this->assertObject($x->parse('acaba'));
        $this->assertFalse($x->parse('baacac'));
    }
}
