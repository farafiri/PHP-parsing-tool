<?php

namespace ParserGenerator\Tests\Examples;

use ParserGenerator\Examples\ArithmeticExpressionParser;
use PHPUnit\Framework\TestCase;

class ArithmeticExpressionParserTest extends TestCase
{
    public function testBase()
    {
        $parser = new ArithmeticExpressionParser();

        $this->assertEquals(15, $parser->getValue('10 + 5'));
        $this->assertEquals(5, $parser->getValue('10 - 5'));
        $this->assertEquals(50, $parser->getValue('10 * 5'));
        $this->assertEquals(2, $parser->getValue('10 / 5'));

        $this->assertEquals(941, $parser->getValue('1 + 10 + 30 + 100 + 300 + 500'));
        $this->assertEquals(180, $parser->getValue('3 * 2 * 2 * 1 * 15'));
        $this->assertEquals(89, $parser->getValue('100 - 10 - 1'));
        $this->assertEquals(4, $parser->getValue('16 / 2 / 2'));
    }

    public function testMixed()
    {
        $parser = new ArithmeticExpressionParser();

        $this->assertEquals(67, $parser->getValue('100 - 3 - 10 + 30 - 50'));
        $this->assertEquals(56, $parser->getValue('10 * 5 + 3 * 2'));
        $this->assertEquals(40, $parser->getValue('10 * 5 - 3 * 2 - 8 / 2'));
    }

    public function testBrackets()
    {
        $parser = new ArithmeticExpressionParser();

        $this->assertEquals(91, $parser->getValue('100 - (10 - 1)'));
        $this->assertEquals(51, $parser->getValue('10 * 5 + 1'));
        $this->assertEquals(60, $parser->getValue('10 * (5 + 1)'));
    }
}
