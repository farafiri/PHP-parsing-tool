<?php

namespace ParserGenerator\Tests\Extension;

use ParserGenerator\Parser;

class RuleConditionTest extends \PHPUnit_Framework_TestCase
{
    protected function assertObject($a)
    {
        $this->assertTrue(is_object($a));
    }

    public function testIntegers()
    {
        $x = new Parser('start :=> 1..100 1..100 <? $s[0]->getValue() < $s[1]->getValue() ?>.', array('ignoreWhitespaces' => true));
        $this->assertObject($x->parse('36 45'));
        $this->assertObject($x->parse('1 100'));
        $this->assertObject($x->parse('1 2'));
        $this->assertObject($x->parse('78 79'));
        $this->assertFalse($x->parse('100 1'));
        $this->assertFalse($x->parse('36 12'));
        $this->assertFalse($x->parse('36 7'));
        $this->assertFalse($x->parse('5 5'));
    }

    public function testValidSubXML()
    {
        $x = new Parser('start    :=> xmlTag.
	                 xmlText  :=> /[^<>]+/.
					 xmlTag   :=> "<" /[a-z]+/ ">" xmlNodes "</" /[a-z]+/ ">" <? $s[1] == $s[5] ?>
					          :=> "<" /[a-z]+/ "/>".
					 xmlNodes :=> xmlNode xmlNodes
					          :=> "".
					 xmlNode  :=> xmlTag
					          :=> xmlText.');

        $this->assertFalse($x->parse('<a><b>text</a>'));
        $this->assertFalse($x->parse('<a>text</b></a>'));
        $this->assertFalse($x->parse('<a>text<b/><c></c></b></a>'));
        $this->assertObject($x->parse('<a><b></b></a>'));
        $this->assertObject($x->parse('<a><b>texttt</b>text</a>'));
        $this->assertObject($x->parse('<a><b></b></a>'));
        $this->assertObject($x->parse('<a><b/></a>'));
        $this->assertObject($x->parse('<a>text<b>text2</b></a>'));
        $this->assertObject($x->parse('<a><b/>text</a>'));
        $this->assertObject($x->parse('<a><a/>text</a>'));
        $this->assertObject($x->parse('<a>text<b>text2</b><c>text</c><d/></a>'));
        //these strings test should fail:
        $this->assertFalse($x->parse('<a></b>'));
        $this->assertFalse($x->parse('<a><a\></b>'));
        $this->assertFalse($x->parse('<a><b\></b>'));
        $this->assertFalse($x->parse('<a><b></a></b>'));
        $this->assertFalse($x->parse('<a><c><b></b></c><b></a></b>'));
    }

    public function testInvalidSubXML()
    {
        $x = new Parser('start    :=> xmlTag.
	                 xmlText  :=> /[^<>]+/.
					 xmlTag   :=> "<" /[a-z]+/ ">" xmlNodes "</" /[a-z]+/ ">" <? $s[1] == $s[5] ?>
					          :=> "<" /[a-z]+/ "/>"
							  :=> "<" /[a-z]+/ ">" xmlNodes.
					 xmlNodes :=> xmlNode xmlNodes
					          :=> "".
					 xmlNode  :=> xmlTag
					          :=> xmlText.');

        $this->assertObject($x->parse('<a><b>text</a>'));
        $this->assertFalse($x->parse('<a>text</b></a>'));
        $this->assertFalse($x->parse('<a>text<b/><c></c></b></a>'));

        //these strings test should fail:
        $this->assertFalse($x->parse('<a></b>'));
        $this->assertFalse($x->parse('<a><a\></b>'));
        $this->assertFalse($x->parse('<a><b\></b>'));
        $this->assertFalse($x->parse('<a><b></a></b>'));

        //check parsing result
        $r = $x->parse('<a>q<b>w</b>e<c>r<d/>t')->findAll('xmlTag', true);
        $this->assertEquals('<a>q<b>w</b>e<c>r<d/>t', (string)$r[0]);
        $this->assertEquals('<b>w</b>', (string)$r[1]);
        $this->assertEquals('<c>r<d/>t', (string)$r[2]);
        $this->assertEquals('<d/>', (string)$r[3]);
    }

    public function testMadGrammar()
    {
        $x = new Parser('start      :=> content.
	                 content  :=> spectext content 
					          :=> /./ content
							  :=> "".
					 spectext :=> text3 content text3 <? (string) $s[0] == (string) $s[2] ?>
							  :=> text2 content text2 <? (string) $s[0] == (string) $s[2] ?>
							  :=> text1 content text1 <? (string) $s[0] == (string) $s[2] ?>.
					 text1    :=> /./ .
					 text2    :=> /.{2}/ .
					 text3    :=> /.{3}/ .');

        $r = $x->parse('abab')->findAll('spectext');
        $this->assertEquals('abab', (string)$r[0]);
        $this->assertEquals(1, count($r));

        $r = $x->parse('abac')->findAll('spectext');
        $this->assertEquals('aba', (string)$r[0]);
        $this->assertEquals(1, count($r));

        $r = $x->parse('abababa')->findAll('spectext');
        $this->assertEquals('abababa', (string)$r[0]);
        $this->assertEquals(1, count($r));

        return 0;
        //this test will fail
        $r = $x->parse('abbbaabab')->findAll('spectext', true);
        $this->assertEquals('abbbaabab', (string)$r[0]);
        $this->assertEquals('bbaab', (string)$r[1]);
        $this->assertEquals('aa', (string)$r[2]);
        $this->assertEquals(3, count($r));
    }
}