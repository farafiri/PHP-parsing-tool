<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 25.02.17
 * Time: 13:51
 */

namespace ParserGenerator\Tests\Extension;

use ParserGenerator\Parser;
use PHPUnit\Framework\TestCase;

class WhiteCharactersTest extends TestCase
{
    protected function assertObject($a): void
    {
        $this->assertTrue(is_object($a));
    }

    public function testNewLine()
    {
        $x = new Parser('start :=> "x"+newLine.');

        $this->assertObject($x->parse("x\nx"));
        $this->assertObject($x->parse("x\r\nx"));
        $this->assertObject($x->parse("x\rx"));

        $x = new Parser('start :=> "x"+newLine.', ['ignoreWhitespaces' => true]);

        $this->assertObject($x->parse("x\nx"));
        $this->assertObject($x->parse("x\r\nx"));
        $this->assertObject($x->parse("x\rx"));
    }

    public function testEatExactNumberOfSpaces()
    {
        $x = new Parser('start :=> space space space.');

        $this->assertFalse($x->parse("  "));
        $this->assertObject($x->parse("   "));
        $this->assertFalse($x->parse("    "));
    }

    public function testEatExactNumberOfWhitespaces()
    {
        $x = new Parser('start :=> whiteSpace whiteSpace whiteSpace.');

        $this->assertFalse($x->parse("  "));
        $this->assertObject($x->parse("   "));
        $this->assertFalse($x->parse("    "));

        $this->assertFalse($x->parse("\r\n\r\n"));
        $this->assertObject($x->parse("\r\n\r\n\r\n"));
        $this->assertFalse($x->parse("\r\n\r\n\r\n\r\n"));
    }

    public function testNegativeLookahead()
    {
        $x = new Parser('start :=> !space whiteSpace !newLine whiteSpace.');

        $this->assertObject($x->parse("\r\n "));
        $this->assertObject($x->parse("\t\t"));
        $this->assertFalse($x->parse("  "));
        $this->assertFalse($x->parse("\n\n"));
    }
}
