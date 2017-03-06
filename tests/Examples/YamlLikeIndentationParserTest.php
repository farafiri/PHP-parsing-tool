<?php

class YamlLikeIndentationParserTest extends PHPUnit_Framework_TestCase {
    public function testBase()
    {
        $parser = new \ParserGenerator\Examples\YamlLikeIndentationParser();

        $this->assertEquals(array("a" => "x"), $parser->getValue("a:x"));
        $this->assertEquals(array(
            "a" => "x",
            "b" => "y"), $parser->getValue(
            "a:x\n" .
            "b:y"));
    }

    public function testIndentsBase()
    {
        $parser = new \ParserGenerator\Examples\YamlLikeIndentationParser();

        $this->assertEquals(array(
            "a" => array(
                "b" => "x")), $parser->getValue(
            "a:\n" .
            " b:x"));
    }

    public function testIndentsCanBeAnyNumberOfSpaces()
    {
        $parser = new \ParserGenerator\Examples\YamlLikeIndentationParser();

        $this->assertEquals(array(
            "a" => array(
                "b" => "x")), $parser->getValue(
            "a:\n" .
            "             b:x"));
    }

    public function testIndentsMultilevel()
    {
        $parser = new \ParserGenerator\Examples\YamlLikeIndentationParser();

        $this->assertEquals(array(
            "a" => array(
                "b" => array(
                    "c" => array(
                        "d" => array(
                            "e" => "x"
            ))))), $parser->getValue(
            "a:\n" .
            "  b:\n" .
            "    c:\n" .
            "      d:\n" .
            "        e:x"));
    }

    public function testIndentsSignificance()
    {
        $parser = new \ParserGenerator\Examples\YamlLikeIndentationParser();

        $this->assertEquals(array(
            "a" => array(
                "b" => array(
                    "c" => "x",
                    "d" => "y"
                ))), $parser->getValue(
            "a:\n" .
            "  b:\n" .
            "    c:x\n" .
            "    d:y\n"));

        $this->assertEquals(array(
            "a" => array(
                "b" => array(
                    "c" => "x"
                    ),
                "d" => "y"
            )), $parser->getValue(
            "a:\n" .
            "  b:\n" .
            "    c:x\n" .
            "  d:y\n"));

        $this->assertEquals(array(
            "a" => array(
                "b" => array(
                    "c" => "x"
                )),
            "d" => "y"
            ), $parser->getValue(
            "a:\n" .
            "  b:\n" .
            "    c:x\n" .
            "d:y\n"));

        // indentation level doesn't match
        $this->assertEquals(false, $parser->getValue(
            "a:\n" .
            "  b:\n" .
            "    c:x\n" .
            " d:y\n"));

        // indentation level doesn't match
        $this->assertEquals(false, $parser->getValue(
            "a:\n" .
            "  b:\n" .
            "    c:x\n" .
            "   d:y\n"));
    }

    public function testComplexExample()
    {
        $parser = new \ParserGenerator\Examples\YamlLikeIndentationParser();

        $this->assertEquals(array(
            "a" => array(
                "b" => array(
                    "c" => "1",
                    "d" => "2"
                ),
                "e" => array(
                    "f" => array(
                        "g" => "3",
                        "h" => "4"
                    )),
                "i" => "5",
                "j" => "6",
                "k" => array(
                    "l" => array(
                        "m" => "7"
                        ),
                    "n" => "8",
                    "o" => "9"
                    ),
                "p" => "10"
                )), $parser->getValue(
            "a:\n" .
            "  b:\n" .
            "    c:1\n" .
            "    d:2\n" .
            "  e:\n" .
            "    f:\n" .
            "      g:3\n" .
            "      h:4\n" .
            "  i:5\n" .
            "  j:6\n" .
            "  k:\n" .
            "    l:\n" .
            "      m:7\n" .
            "    n:8\n" .
            "    o:9\n" .
            "  p:10\n"));
    }
} 