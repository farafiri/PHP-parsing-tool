<?php

namespace ParserGenerator\Tests\Util;

use PHPUnit\Framework\TestCase;
use ParserGenerator\Util\Regex;

class RegexTest extends TestCase
{
    public function testCanBeEmpty()
    {
        $x = Regex::getInstance();
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
        $assocCharacters = [];
        foreach ($chars as $char) {
            $assocCharacters[$char] = true;
        }

        $this->assertEquals($assocCharacters, Regex::getInstance()->getStartCharacters($regex));
    }

    public function testGetStartCharacters()
    {
        $this->assertCanStart(['a'], '/a/');
        $this->assertCanStart(['a'], '/ab/');
        $this->assertCanStart(['a'], '/a+b/');
        $this->assertCanStart(['a', 'b'], '/a*b/');
        $this->assertCanStart(['a', 'b'], '/a?b/');
        $this->assertCanStart(['a'], '/a+?b/');
        $this->assertCanStart(['a', 'b'], '/(a|b)/');
        $this->assertCanStart(['a', 'c'], '/(ab|c)/');
        $this->assertCanStart(['a', 'b', 'c', 'd', 'e'], '/a?b?c?d?efg/');
        $this->assertCanStart(['a', 'b', 'c'], '/(a|b?)c/');
        $this->assertCanStart(['a', 'b', 'c'], '/(a|b)?c/');
        $this->assertCanStart(['a', 'b'], '/a{0,3}b/');
        $this->assertCanStart(['a'], '/a{1,3}b/');
        $this->assertCanStart(['a', 'b', 'c'], '/[abc]/');
        $this->assertCanStart(['a', 'b', 'c', 'd'], '/[a-d]/');
        $this->assertCanStart(['h', 'a', 'b', 'c'], '/[ha-c]/');
        $this->assertCanStart(['-', 'a', 'b', 'c'], '/[-a-c]/');
        $this->assertCanStart(['a', 'b', 'c', '1', '2', '3'], '/[a-c1-3]/');
        $this->assertCanStart(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], '/\\d/');
        $this->assertCanStart(['['], '/\\[/');
        $this->assertCanStart(["\n", "\r", " ", "\t"], '/\\s/');
        $this->assertCanStart(["\n", "\r", " ", "\t", "j"], '/[\\sj]/');
        $this->assertEquals(255,
            count(Regex::getInstance()->getStartCharacters('/./'))); //256 - 1 cause \n is out
    }

    protected function checkStringGenerate($regex, $results, $maxLength = 10)
    {
        $x = Regex::getInstance();
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
        $this->checkStringGenerate('/a/', ['a']);
        $this->checkStringGenerate('/asd/', ['asd']);
        $this->checkStringGenerate('/(a|b|c)/', ['a', 'b', 'c']);
        $this->checkStringGenerate('/a?/', ['a', '']);
        $this->checkStringGenerate('/a?b?/', ['a', 'b', 'ab', '']);
        $this->checkStringGenerate('/(a|b|c)?/', ['a', 'b', 'c', '']);
        $this->checkStringGenerate('/a*/', ['', 'a', 'aa', 'aaa'], 3);
        $this->checkStringGenerate('/a+/', ['a', 'aa', 'aaa'], 3);
        $this->checkStringGenerate('/a{2}/', ['aa']);
        $this->checkStringGenerate('/a{2,3}/', ['aa', 'aaa']);
        $this->checkStringGenerate('/(a|(c|de)?f)/', ['a', 'cf', 'def', 'f']);
        $this->checkStringGenerate('/[abc]/', ['a', 'b', 'c']);
        $this->checkStringGenerate('/[a-dz]/', ['a', 'b', 'c', 'd', 'z']);
        $this->checkStringGenerate('/\d/', ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9']);
    }
}
