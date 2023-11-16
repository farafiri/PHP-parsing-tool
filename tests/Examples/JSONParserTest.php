<?php

namespace ParserGenerator\Tests\Examples;

use ParserGenerator\Examples\JSONParser;
use PHPUnit\Framework\TestCase;

class JSONParserTest extends TestCase
{
    public function testSimpleValues()
    {
        $jsonParser = new JSONParser();
        $this->assertTrue($jsonParser->getValue('true'));
        $this->assertFalse($jsonParser->getValue('false'));
        $this->assertEquals(23, $jsonParser->getValue('23'));
        $this->assertEquals(0, $jsonParser->getValue('0'));
        $this->assertEquals("Lorem ipsum", $jsonParser->getValue('"Lorem ipsum"'));
        $this->assertEquals("", $jsonParser->getValue('""'));
        $this->assertEquals("Lorem \n ipsum", $jsonParser->getValue('"Lorem \n ipsum"'));
    }

    public function testArray()
    {
        $jsonParser = new JSONParser();
        $this->assertEquals([], $jsonParser->getValue('[]'));
        $this->assertEquals([true], $jsonParser->getValue('[true]'));
        $this->assertEquals([1, 2, 3], $jsonParser->getValue('[1, 2, 3]'));
        $this->assertEquals(["1, 2", "3"], $jsonParser->getValue('["1, 2", "3"]'));
        $this->assertEquals([[], [[]]], $jsonParser->getValue('[[],[[]]]'));
        $this->assertEquals([["a1", "a2"], ["b1", "b2"]],
            $jsonParser->getValue('[["a1", "a2"], ["b1", "b2"]]'));
    }

    public function testObject()
    {
        $jsonParser = new JSONParser();
        $this->assertEquals([], $jsonParser->getValue('{}'));
        $this->assertEquals(["x" => "x"], $jsonParser->getValue('{"x":"x"}'));
        $this->assertEquals(["x" => 4, "y" => 5, "color" => "red", "visible" => true],
            $jsonParser->getValue('{"x": 4, "y": 5, "color":"red", "visible":true}'));
        $this->assertEquals(["a" => [], "b" => ["c" => "c"]],
            $jsonParser->getValue('{"a" : {}, "b":{"c": "c"}}'));

    }

    public function testMixed()
    {
        $jsonParser = new JSONParser();
        $this->assertEquals(["x" => [["c" => "c"], 6]], $jsonParser->getValue('{"x":[{"c":"c"}, 6]}'));
    }
}
