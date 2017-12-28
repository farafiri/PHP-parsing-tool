<?php

namespace ParserGenerator\Tests\Examples;

use PHPUnit\Framework\TestCase;

class CSVParserTest extends TestCase
{
    public function testBase()
    {
        $parser = new \ParserGenerator\Examples\CSVParser();

        $expected = array(
            array('r1c1', 'r1c2'),
            array('r2c1', 'r2c2')
        );

        $this->assertEquals($expected, $parser->parseCSV("r1c1,r1c2\nr2c1,r2c2"));
    }

    public function testQuoted()
    {
        $parser = new \ParserGenerator\Examples\CSVParser();

        $expected = array(
            array('r1c1', 'r1c2'),
            array('r2c1', 'r2c2')
        );

        $this->assertEquals($expected, $parser->parseCSV("\"r1c1\" , \"r1c2\"\n\"r2c1\",\"r2c2\""));
    }

    public function testPreserveSpaces()
    {
        $parser = new \ParserGenerator\Examples\CSVParser();

        $expected = array(
            array('  c1  ', ' c2 ')
        );

        $this->assertEquals($expected, $parser->parseCSV("  c1  , c2 "));
    }

    public function testProperEscaping()
    {
        $parser = new \ParserGenerator\Examples\CSVParser();

        $expected = array(
            array('text "quot"', ", \n"),
            array('\n', 'a')
        );

        $this->assertEquals($expected, $parser->parseCSV("\"text \"\"quot\"\"\", \", \n\" \n  \"\\n\",a"));
    }
}
