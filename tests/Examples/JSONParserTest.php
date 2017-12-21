<?php

class JSONParserTest extends PHPUnit_Framework_TestCase
{
    public function testSimpleValues()
    {
        $jsonParser = new \ParserGenerator\Examples\JSONParser();
        $this->assertEquals(true, $jsonParser->getValue('true'));
        $this->assertEquals(false, $jsonParser->getValue('false'));
        $this->assertEquals(23, $jsonParser->getValue('23'));
        $this->assertEquals(0, $jsonParser->getValue('0'));
        $this->assertEquals("Lorem ipsum", $jsonParser->getValue('"Lorem ipsum"'));
        $this->assertEquals("", $jsonParser->getValue('""'));
        $this->assertEquals("Lorem \n ipsum", $jsonParser->getValue('"Lorem \n ipsum"'));
    }

    public function testArray()
    {
        $jsonParser = new \ParserGenerator\Examples\JSONParser();
        $this->assertEquals(array(), $jsonParser->getValue('[]'));
        $this->assertEquals(array(true), $jsonParser->getValue('[true]'));
        $this->assertEquals(array(1, 2, 3), $jsonParser->getValue('[1, 2, 3]'));
        $this->assertEquals(array("1, 2", "3"), $jsonParser->getValue('["1, 2", "3"]'));
        $this->assertEquals(array(array(), array(array())), $jsonParser->getValue('[[],[[]]]'));
        $this->assertEquals(array(array("a1", "a2"), array("b1", "b2")),
            $jsonParser->getValue('[["a1", "a2"], ["b1", "b2"]]'));
    }

    public function testObject()
    {
        $jsonParser = new \ParserGenerator\Examples\JSONParser();
        $this->assertEquals(array(), $jsonParser->getValue('{}'));
        $this->assertEquals(array("x" => "x"), $jsonParser->getValue('{"x":"x"}'));
        $this->assertEquals(array("x" => 4, "y" => 5, "color" => "red", "visible" => true),
            $jsonParser->getValue('{"x": 4, "y": 5, "color":"red", "visible":true}'));
        $this->assertEquals(array("a" => array(), "b" => array("c" => "c")),
            $jsonParser->getValue('{"a" : {}, "b":{"c": "c"}}'));

    }

    public function testMixed()
    {
        $jsonParser = new \ParserGenerator\Examples\JSONParser();
        $this->assertEquals(array("x" => array(array("c" => "c"), 6)), $jsonParser->getValue('{"x":[{"c":"c"}, 6]}'));
    }
}
