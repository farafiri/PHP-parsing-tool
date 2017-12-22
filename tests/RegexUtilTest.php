<?php

class RegexUtilTest extends PHPUnit_Framework_TestCase
{
    public function testCanBeEmpty()
    {
        $x = \ParserGenerator\RegexUtil::getInstance();
        $this->assertFalse($x->canBeEmpty('/a/'));
        $this->assertFalse($x->canBeEmpty('/\s/'));
        $this->assertTrue($x->canBeEmpty('/$/'));
        $this->assertFalse($x->canBeEmpty('/aab/'));
        $this->assertFalse($x->canBeEmpty('/./'));
        $this->assertFalse($x->canBeEmpty('/[abc]/'));
        $this->assertFalse($x->canBeEmpty('/[^abc]/'));
        $this->assertTrue($x->canBeEmpty('/a*/'));
        $this->assertTrue($x->canBeEmpty('/a?/'));
        $this->assertFalse($x->canBeEmpty('/a+/'));
        $this->assertTrue($x->canBeEmpty('/[a]*/'));
        $this->assertFalse($x->canBeEmpty('/[a]+/'));
        $this->assertFalse($x->canBeEmpty('/a*b/'));
        $this->assertTrue($x->canBeEmpty('/a*b*/'));
        $this->assertFalse($x->canBeEmpty('/a*b*c/'));
        $this->assertFalse($x->canBeEmpty('/a{3}/'));
        $this->assertFalse($x->canBeEmpty('/a{3,9}/'));
        $this->assertTrue($x->canBeEmpty('/a{0,3}/'));
        $this->assertFalse($x->canBeEmpty('/a+?/'));
        $this->assertTrue($x->canBeEmpty('/a*?/'));
        $this->assertFalse($x->canBeEmpty('/a|b/'));
        $this->assertTrue($x->canBeEmpty('/a|/'));
        $this->assertTrue($x->canBeEmpty('/a|a*/'));
        $this->assertTrue($x->canBeEmpty('/a*|b*/'));
        $this->assertFalse($x->canBeEmpty('/(a|b)/'));
        $this->assertTrue($x->canBeEmpty('/(a|)/'));
        $this->assertTrue($x->canBeEmpty('/(a|a*)/'));
        $this->assertTrue($x->canBeEmpty('/(a*|b*)/'));
        $this->assertTrue($x->canBeEmpty('/(a|a*)b*/'));
        $this->assertFalse($x->canBeEmpty('/(a|a*)b/'));
        $this->assertTrue($x->canBeEmpty('/(a|b)?/'));
        $this->assertFalse($x->canBeEmpty('/(a|b)+/'));
        $this->assertTrue($x->canBeEmpty('/(a|$)/'));
        $this->assertTrue($x->canBeEmpty('/(a|^)+/'));
    }

    protected function assertCanStart($chars, $regex)
    {
        $assocCharacters = array();
        foreach ($chars as $char) {
            $assocCharacters[$char] = true;
        }

        $this->assertEquals($assocCharacters, \ParserGenerator\RegexUtil::getInstance()->getStartCharacters($regex));
    }

    public function testGetStartCharacters()
    {
        $this->assertCanStart(array('a'), '/a/');
        $this->assertCanStart(array('a'), '/ab/');
        $this->assertCanStart(array('a'), '/a+b/');
        $this->assertCanStart(array('a', 'b'), '/a*b/');
        $this->assertCanStart(array('a', 'b'), '/a?b/');
        $this->assertCanStart(array('a'), '/a+?b/');
        $this->assertCanStart(array('a', 'b'), '/(a|b)/');
        $this->assertCanStart(array('a', 'c'), '/(ab|c)/');
        $this->assertCanStart(array('a', 'b', 'c', 'd', 'e'), '/a?b?c?d?efg/');
        $this->assertCanStart(array('a', 'b', 'c'), '/(a|b?)c/');
        $this->assertCanStart(array('a', 'b', 'c'), '/(a|b)?c/');
        $this->assertCanStart(array('a', 'b'), '/a{0,3}b/');
        $this->assertCanStart(array('a'), '/a{1,3}b/');
        $this->assertCanStart(array('a', 'b', 'c'), '/[abc]/');
        $this->assertCanStart(array('a', 'b', 'c', 'd'), '/[a-d]/');
        $this->assertCanStart(array('h', 'a', 'b', 'c'), '/[ha-c]/');
        $this->assertCanStart(array('-', 'a', 'b', 'c'), '/[-a-c]/');
        $this->assertCanStart(array('a', 'b', 'c', '1', '2', '3'), '/[a-c1-3]/');
        $this->assertCanStart(array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'), '/\\d/');
        $this->assertCanStart(array('['), '/\\[/');
        $this->assertCanStart(array("\n", "\r", " ", "\t"), '/\\s/');
        $this->assertCanStart(array("\n", "\r", " ", "\t", "j"), '/[\\sj]/');
        $this->assertEquals(255,
            count(\ParserGenerator\RegexUtil::getInstance()->getStartCharacters('/./'))); //256 - 1 cause \n is out
    }

    protected function checkStringGenerate($regex, $results, $maxLength = 10)
    {
        $x = \ParserGenerator\RegexUtil::getInstance();
        $matches = 0;
        for ($i = 0; $i < 1000; $i++) {
            $s = $x->generateString($regex);
            if (strlen($s) <= $maxLength) {
                if (in_array($s, $results)) {
                    if (++$matches > 10) {
                        $this->assertTrue(true);
                        return true;
                    }
                } else {
                    $this->assertFalse(true);
                }
            }
        }

        $this->assertFalse(true);
    }

    public function testRegexGenarateString()
    {
        srand(10);
        $this->checkStringGenerate('/a/', array('a'));
        $this->checkStringGenerate('/asd/', array('asd'));
        $this->checkStringGenerate('/(a|b|c)/', array('a', 'b', 'c'));
        $this->checkStringGenerate('/a?/', array('a', ''));
        $this->checkStringGenerate('/a?b?/', array('a', 'b', 'ab', ''));
        $this->checkStringGenerate('/(a|b|c)?/', array('a', 'b', 'c', ''));
        $this->checkStringGenerate('/a*/', array('', 'a', 'aa', 'aaa'), 3);
        $this->checkStringGenerate('/a+/', array('a', 'aa', 'aaa'), 3);
        $this->checkStringGenerate('/a{2}/', array('aa'));
        $this->checkStringGenerate('/a{2,3}/', array('aa', 'aaa'));
        $this->checkStringGenerate('/(a|(c|de)?f)/', array('a', 'cf', 'def', 'f'));
        $this->checkStringGenerate('/[abc]/', array('a', 'b', 'c'));
        $this->checkStringGenerate('/[a-dz]/', array('a', 'b', 'c', 'd', 'z'));
        $this->checkStringGenerate('/\d/', array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'));
    }
}
