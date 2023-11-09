<?php

namespace ParserGenerator\Tests\Extension;

use ParserGenerator\Parser;
use ParserGenerator\SyntaxTreeNode\Leaf;
use ParserGenerator\SyntaxTreeNode\Root;
use PHPUnit\Framework\TestCase;

class ItemRestrictionsTest extends TestCase
{
    protected function assertObject($a): void
    {
        $this->assertTrue(is_object($a));
    }

    public function testBaseContain()
    {
        $x = new Parser('start :=> text contain "aa".');

        $this->assertFalse($x->parse('bcd'));
        $this->assertFalse($x->parse('acba'));
        $this->assertFalse($x->parse('a'));
        $this->assertObject($x->parse('aa'));
        $this->assertObject($x->parse('aabcdef'));
        $this->assertObject($x->parse('bcaadef'));
        $this->assertObject($x->parse('bcdefaa'));
        $this->assertObject($x->parse('bcaadeaaf'));
        $this->assertObject($x->parse('bcdaaaef'));

        $x = new Parser('start :=> text contain /[ab]{2}/ "b".');

        $this->assertFalse($x->parse('bbcd'));
        $this->assertFalse($x->parse('acbaa'));
        $this->assertFalse($x->parse('a'));
        $this->assertFalse($x->parse(''));
        $this->assertFalse($x->parse('aac'));
        $this->assertFalse($x->parse('ab'));

        $this->assertEquals(new Root('start', 0, [
            new Leaf("aa"),
            new Leaf("b"),
        ]), $x->parse("aab"));

        $this->assertEquals(new Root('start', 0, [
            new Leaf("sdabag"),
            new Leaf("b"),
        ]), $x->parse("sdabagb"));

        $this->assertEquals(new Root('start', 0, [
            new Leaf("ba"),
            new Leaf("b"),
        ]), $x->parse("bab"));

        $x = new Parser('start :=> text contain /[ab]{2}/ text.');

        $this->assertFalse($x->parse('bcd'));
        $this->assertFalse($x->parse('b'));
        $this->assertFalse($x->parse('a'));
        $this->assertFalse($x->parse(''));

        $this->assertEquals(new Root('start', 0, [
            new Leaf("ba"),
            new Leaf("b"),
        ]), $x->parse("bab"));

        $this->assertEquals(new Root('start', 0, [
            new Leaf("xba"),
            new Leaf("bb"),
        ]), $x->parse("xbabb"));

        $this->assertEquals(new Root('start', 0, [
            new Leaf("xba"),
            new Leaf("xbbxaa"),
        ]), $x->parse("xbaxbbxaa"));
    }

    public function testWithNodes()
    {
        $x = new Parser('start           :=> text contain textInBranckets.
                         textInBranckets :=> "(" text ")"
                                         :=> "[" text "]".');

        $this->assertFalse($x->parse('asdg'));
        $this->assertFalse($x->parse(''));
        $this->assertFalse($x->parse('as(sd'));
        $this->assertFalse($x->parse('as[sd'));
        $this->assertFalse($x->parse('[asd'));
        $this->assertFalse($x->parse('['));
        $this->assertFalse($x->parse('a[df[gdd(fsd(df'));
        $this->assertFalse($x->parse('as[d)g'));
        $this->assertFalse($x->parse('as(d]g'));
        $this->assertFalse($x->parse('d]dsf[d'));
        $this->assertObject($x->parse('as[d]g'));
        $this->assertObject($x->parse('as(d)g'));
        $this->assertObject($x->parse('as[[)]g'));
        $this->assertObject($x->parse('[]aaa'));
        $this->assertObject($x->parse('aaa[]'));
        $this->assertFalse($x->parse('as['));
        $this->assertFalse($x->parse(']as'));
        $this->assertObject($x->parse('sd[ss ]dg[sdf]g'));
        $this->assertObject($x->parse('()'));
        $this->assertObject($x->parse('[asdd]'));
        $this->assertObject($x->parse(']df[asdd]gg['));
        $this->assertObject($x->parse(']()['));
        $this->assertObject($x->parse('[)(]('));
    }

    public function testNot()
    {
        $x = new Parser('start :=> text not contain "aa".');

        $this->assertObject($x->parse('bcd'));
        $this->assertObject($x->parse('acba'));
        $this->assertObject($x->parse('a'));
        $this->assertFalse($x->parse('aa'));
        $this->assertFalse($x->parse('aabcdef'));
        $this->assertFalse($x->parse('bcaadef'));
        $this->assertFalse($x->parse('bcdefaa'));
        $this->assertFalse($x->parse('bcaadeaaf'));
        $this->assertFalse($x->parse('bcdaaaef'));
    }

    public function testLogicConditionsOr()
    {
        $x = new Parser('start :=> text contain "a" or contain "b".');

        $this->assertFalse($x->parse('c'));
        $this->assertObject($x->parse('a'));
        $this->assertObject($x->parse('b'));
        $this->assertObject($x->parse('ab'));

        $x = new Parser('start :=> text contain "a" or contain "b" or contain "c".');

        $this->assertFalse($x->parse('d'));
        $this->assertObject($x->parse('a'));
        $this->assertObject($x->parse('b'));
        $this->assertObject($x->parse('ac'));
        $this->assertObject($x->parse('c'));
        $this->assertObject($x->parse('cab'));

        $x = new Parser('start :=> text contain "a" or not contain "b".');

        $this->assertFalse($x->parse('b'));
        $this->assertFalse($x->parse('erbbt'));
        $this->assertObject($x->parse(''));
        $this->assertObject($x->parse('rtyh'));
        $this->assertObject($x->parse('ab'));
        $this->assertObject($x->parse('ac'));
    }

    public function testLogicConditionsAnd()
    {
        $x = new Parser('start :=> text contain "a" and contain "b".');

        $this->assertFalse($x->parse('c'));
        $this->assertFalse($x->parse('a'));
        $this->assertFalse($x->parse('b'));
        $this->assertObject($x->parse('ab'));

        $x = new Parser('start :=> text contain "a" and contain "b" and contain "c".');

        $this->assertFalse($x->parse('d'));
        $this->assertFalse($x->parse('a'));
        $this->assertFalse($x->parse('b'));
        $this->assertFalse($x->parse('ac'));
        $this->assertFalse($x->parse('c'));
        $this->assertObject($x->parse('cab'));

        $x = new Parser('start :=> text contain "a" and not contain "b".');

        $this->assertFalse($x->parse('b'));
        $this->assertFalse($x->parse('erbbt'));
        $this->assertFalse($x->parse(''));
        $this->assertFalse($x->parse('rtyh'));
        $this->assertFalse($x->parse('ab'));
        $this->assertObject($x->parse('ac'));
    }

    public function testLogicConditionsGroups()
    {
        $x = new Parser('start :=> text (contain "a" and not contain "b") or (not contain "a" and contain "b").');

        $this->assertFalse($x->parse('c'));
        $this->assertObject($x->parse('a'));
        $this->assertObject($x->parse('b'));
        $this->assertFalse($x->parse('ab'));

        $x = new Parser('start :=> text contain "c" and (contain "b" or contain "a").');

        $this->assertFalse($x->parse('c'));
        $this->assertObject($x->parse('ca'));
        $this->assertObject($x->parse('cb'));
        $this->assertObject($x->parse('cab'));
        $this->assertFalse($x->parse(''));
        $this->assertFalse($x->parse('a'));
        $this->assertFalse($x->parse('b'));
        $this->assertFalse($x->parse('ab'));
    }

    public function testWithLookaroundSimple()
    {
        $x = new Parser('start :=> (text contain ?"aaa") text.');

        $this->assertEquals(new Root('start', 0, [
            new Leaf("qaaba"),
            new Leaf("aacaaaa"),
        ]), $x->parse("qaabaaacaaaa"));
    }

    public function testWithLookaround()
    {
        /* Lets define grammar where:
         * text where all "<<<" are closed with ">>>"
         * free "<<<" or ">>>" are forbidden.
         * without "not contain ..." string "<<<" would be parsed as well
         */
        $x = new Parser('start     :=> properText+tag.
                         tag       :=> open properText close.
                         properText:=> text not contain ?(open|close).
                         open      :=> "<<<".
                         close     :=> ">>>".');

        $this->assertObject($x->parse("asd<<<asdfg>>>fsdf"));
        $this->assertObject($x->parse("as>d<<<as<<dfg>>>fsd><<f"));
        $this->assertFalse($x->parse("a<<<b"));
    }

    public function testIsBasic()
    {
        $x = new Parser('start :=> text is "expected text".');

        $this->assertFalse($x->parse("random text"));
        $this->assertFalse($x->parse(""));
        $this->assertFalse($x->parse("expected"));
        $this->assertFalse($x->parse("text"));
        $this->assertObject($x->parse("expected text"));
    }

    public function testIsWithRegex()
    {
        //matches only text containing exactly two letters "a" and five letters "b" in any order
        $x = new Parser('start :=> /([^a]*a){2}[^a]*/ is /([^b]*b){5}[^b]*/ is /[ab]+/.');

        $this->assertFalse($x->parse(''));
        $this->assertFalse($x->parse('aa'));
        $this->assertFalse($x->parse('bbbbb'));
        $this->assertObject($x->parse('aabbbbb'));
        $this->assertObject($x->parse('bbbbbaa'));
        $this->assertFalse($x->parse('aacbbbbb'));
        $this->assertFalse($x->parse('aaabbbbb'));
        $this->assertFalse($x->parse('aabbbbbb'));
        $this->assertObject($x->parse('bbaabbb'));
        $this->assertObject($x->parse('bbabbab'));
    }

    public function testIsWithNot()
    {
        $x = new Parser('start :=> text not is ("forbidden"|"words").');

        $this->assertObject($x->parse("asdf"));
        $this->assertObject($x->parse("keywords"));
        $this->assertObject($x->parse("word"));
        $this->assertObject($x->parse("xforbidden"));
        $this->assertFalse($x->parse("words"));
        $this->assertFalse($x->parse("forbidden"));
    }

    public function testFollowedBy()
    {
        $this->markTestSkipped('TODO');

        $x = new Parser('start :=> text followed by "a" text.');

        $this->assertEquals(new Root('start', 0, [
            new Leaf("qw"),
            new Leaf("adgahaab"),
        ]), $x->parse("qwadgahaab"));

        $this->assertEquals(new Root('start', 0, [
            new Leaf(""),
            new Leaf("aaa"),
        ]), $x->parse("aaa"));
    }
}
