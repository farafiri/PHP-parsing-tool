<?php

namespace ParserGenerator\Tests\Extension;

use ParserGenerator\Parser;
use PHPUnit\Framework\TestCase;

class ParametrizedNodeTest extends TestCase
{
    protected function assertObject($a)
    {
        $this->assertTrue(is_object($a));
    }

    public function testBasic()
    {
        $x = new Parser('start           :=> test<"x">.
                         test<paramtest> :=> "y" paramtest.');

        $this->assertObject($x->parse("yx"));
        $this->assertFalse($x->parse("yy"));
    }

    public function testTwoBranches()
    {
        $x = new Parser('start           :=> test<"x">
                                         :=> test<"o">.
                         test<separator> :=> "a"*separator.');

        $this->assertObject($x->parse("a"));
        $this->assertObject($x->parse("axa"));
        $this->assertObject($x->parse("aoa"));
        $this->assertObject($x->parse("axaxa"));
        $this->assertObject($x->parse("aoaoa"));
        $this->assertFalse($x->parse("axaoa"));
        $this->assertFalse($x->parse("aoaxa"));
    }

    public function testLotOfParams1()
    {
        $x = new Parser('start           :=> test<"x", "y", "z", "v">.
                         test<x,y,z,v>   :=> x y z v (z y x)?.');

        $this->assertObject($x->parse("xyzv"));
        $this->assertObject($x->parse("xyzvzyx"));
        $this->assertFalse($x->parse("xyzvzyv"));
        $this->assertFalse($x->parse("xyzvzy"));
        $this->assertFalse($x->parse("xyz"));
    }

    public function testNestedParams()
    {
        $x = new Parser('start        :=> test1<"x", "y">.
                         test1<x,y>   :=> test2<y, x>.
                         test2<x,y>   :=> "a" test3<x, y>.
                         test3<x,y>   :=> "b" x y.');

        $this->assertObject($x->parse("abyx"));
        $this->assertFalse($x->parse("abxy"));
    }

    public function testRecursion()
    {
        $x = new Parser('start           :=> list<"", "x">.
                         list<x, e>      :=> ?x list<(x e), e>
                                         :=> x+",".');

        $this->assertObject($x->parse("xx,xx"));
        $this->assertObject($x->parse("xxx"));
        $this->assertObject($x->parse(",,,"));
        $this->assertObject($x->parse("xx,xx"));
        $this->assertObject($x->parse("xxx,xxx"));
        $this->assertObject($x->parse("xx,xx,xx"));
        $this->assertFalse($x->parse("xx,xx,xxx"));
        $this->assertFalse($x->parse("xxx,xx,xx"));
        $this->assertFalse($x->parse("xx,xx,x"));
        $this->assertFalse($x->parse("x,xx,xx"));
        $this->assertFalse($x->parse("xxx,xx,xxxx"));
    }

    public function testLotOfParams()
    {
        $x = new Parser('start                :=> test<"","x","","y","","z">.
                         test<xs,x,ys,y,zs,z> :=> ?xs test<(xs x),x,(ys y),y,(zs z),z>
                                              :=> xs ys zs.');

        $this->assertObject($x->parse("xyz"));
        $this->assertObject($x->parse("xxyyzz"));
        $this->assertObject($x->parse("xxxyyyzzz"));
        $this->assertObject($x->parse("xxxxyyyyzzzz"));
        $this->assertFalse($x->parse("xy"));
        $this->assertFalse($x->parse("xxyz"));
        $this->assertFalse($x->parse("xxxyyyzz"));
        $this->assertFalse($x->parse("xxxyyyzzzz"));
        $this->assertFalse($x->parse("xxxxyyyzzz"));
        $this->assertFalse($x->parse("xxyyyzzz"));
    }

    public function testParametrizedInParams()
    {
        $x = new Parser('start :=> list<"[", "]", list<"{", "}", list<"[", "]", /[a-z]+/, ";">, ",">, ",">.
                         list<start, stop, elem, separator> :=> start elem*separator stop.');

        $this->assertObject($x->parse('[]'));
        $this->assertObject($x->parse('[{}]'));
        $this->assertObject($x->parse('[{[]}]'));
        $this->assertObject($x->parse('[{[a;b],[b]},{[nmn]}]'));
        $this->assertFalse($x->parse('[[[]]]'));
        $this->assertFalse($x->parse('[{[a,b]}]'));
        $this->assertFalse($x->parse('[{[a;b];[a]}]'));
    }

    public function testCounter()
    {
        //n in binary followed by ":" and n characters
        $x = new Parser('start                            :=> counter<":", char>.
                         counter<separator, elem>         :=> _counter<separator, elem, "">.
                         _counter<separator, elem, elems> :=> "1" _counter<separator, elem, (elems elems elem)>
                                                          :=> "0" _counter<separator, elem, (elems elems)>
                                                          :=> separator elems.
                         char                             :=> /./ .
                         ');

        $this->assertObject($x->parse("101:12345"));
        $this->assertFalse($x->parse("101:1234"));
        $this->assertFalse($x->parse("101:123456"));

        $this->assertObject($x->parse("110:123456"));
        $this->assertFalse($x->parse("110:12345"));
        $this->assertFalse($x->parse("110:1234567"));

        $this->assertObject($x->parse("10111:12345678901234567890123"));
        $this->assertFalse($x->parse("10111:1234567890123456789012"));
        $this->assertFalse($x->parse("10111:123456789012345678901234"));
    }

    public function testCounter2()
    {
        $x = new Parser('start              :=> counter<char, "1">+.
                         counter<elems,num> :=> ?elems counter<(elems elems char),(num "1")>
                                            :=> ?elems counter<(elems elems),(num "0")>
                                            :=> elems num.
                         char               :=> /./ .
                         ');

        $toList = function ($str) use ($x) {
            $parsed = $x->parse($str);
            if (!$parsed) {
                return false;
            }

            return array_map('strval', $parsed->getSubnode(0)->getMainNodes());
        };

        $this->assertEquals(["11", "11"], $toList("1111"));
        $this->assertEquals(["1010", "11"], $toList("101011"));
        $this->assertEquals(["10110101", "0010"], $toList("101101010010"));
        $this->assertEquals(["1001001111", "11011", "1011100"], $toList("1001001111110111011100"));
        $this->assertEquals(["11111101111010"], $toList("11111101111010"));
        $this->assertEquals(["1111110111", "1110"], $toList("11111101111110"));

        $this->assertFalse($x->parse("0000000000000"));
        $this->assertFalse($x->parse("1001001"));
        $this->assertFalse($x->parse("10110111"));
    }
}
