<?php

namespace ParserGenerator\Tests\GrammarNodes;

use ParserGenerator\GrammarNode\Numeric;
use PHPUnit\Framework\TestCase;

class GrammarNodeNumericTest extends TestCase
{
    protected function assertNodeEquals($expected, $node): void
    {
        $this->assertTrue(is_array($node) && isset($node['node']));
        $this->assertEquals($expected, (string)$node['node']);
    }

    public function testBasic()
    {
        $x = new Numeric();

        $this->assertNodeEquals('5', $x->rparse('5b123vb', 0, []));
        $this->assertNodeEquals('123', $x->rparse('5b123vb', 2, []));
        $this->assertNodeEquals('-123', $x->rparse('5b-123vb', 2, []));
        $this->assertFalse($x->rparse('ab123vb', 2, [5 => 5]));
        $this->assertNodeEquals('0', $x->rparse('ab0x3vb', 2, []));
        $this->assertFalse($x->rparse('ab0x3vb', 2, [3 => 3]));
    }

    public function testBase()
    {
        $x = new Numeric(['formatHex' => true]);

        $this->assertNodeEquals('123', $x->rparse('ab123vb', 2, []));
        $this->assertFalse($x->rparse('ab123vb', 2, [5 => 5]));
        $this->assertNodeEquals('0x3', $x->rparse('ab0x3vb', 2, []));
        $this->assertNodeEquals('0', $x->rparse('ab0x3vb', 2, [5 => 5]));
        $this->assertFalse($x->rparse('ab0x3vb', 2, [3 => 3, 5 => 5]));
    }

    public function testMinMax()
    {
        $x = new Numeric([
            'formatHex' => true,
            'formatBin' => true,
            'min' => 7,
            'max' => 250,
        ]);

        $this->assertFalse($x->rparse('-8', 0, []));
        $this->assertFalse($x->rparse('6', 0, []));
        $this->assertFalse($x->rparse('251', 0, []));
        $this->assertFalse($x->rparse('1000', 0, []));
        $this->assertFalse($x->rparse('5000', 0, []));
        $this->assertNodeEquals('7', $x->rparse('7', 0, []));
        $this->assertNodeEquals('90', $x->rparse('90', 0, []));
        $this->assertNodeEquals('57', $x->rparse('57', 0, []));
        $this->assertNodeEquals('250', $x->rparse('250', 0, []));
        $this->assertNodeEquals('0x7', $x->rparse('0x7', 0, []));
        $this->assertNodeEquals('0xfa', $x->rparse('0xfa', 0, []));
        $this->assertFalse($x->rparse('0x250', 0, []));
        $this->assertFalse($x->rparse('0xfb', 0, []));
        $this->assertFalse($x->rparse('0x110', 0, []));
        $this->assertNodeEquals('0b111', $x->rparse('0b111', 0, []));
        $this->assertNodeEquals('0b11111010', $x->rparse('0b11111010', 0, []));
        $this->assertFalse($x->rparse('0b11111011', 0, []));
    }
}
