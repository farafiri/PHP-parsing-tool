<?php

namespace ParserGenerator\Tests\Extension;

use ParserGenerator\Parser;

class IntegerTest extends \PHPUnit_Framework_TestCase
{
    protected function assertObject($a)
    {
        $this->assertTrue(is_object($a));
    }

    public function testIntExtension()
    {
        $x = new Parser('start :=> 2..6.');
        $this->assertFalse($x->parse('0'));
        $this->assertFalse($x->parse('1'));
        $this->assertObject($x->parse('2'));
        $this->assertObject($x->parse('3'));
        $this->assertObject($x->parse('5'));
        $this->assertObject($x->parse('6'));
        $this->assertFalse($x->parse('7'));
        $this->assertFalse($x->parse('10'));
        $this->assertFalse($x->parse('20'));

        $this->assertFalse($x->parse('0x5'));
        $this->assertFalse($x->parse('05'));

        $x = new Parser('start :=> -inf..inf.');
        $this->assertObject($x->parse('0'));
        $this->assertObject($x->parse('1'));
        $this->assertObject($x->parse('-1'));
        $this->assertObject($x->parse('26843562'));
        $this->assertObject($x->parse('-26843562'));

        //if we have rage in hex then hex is proper format
        $x = new Parser('start :=> 0x0..0xff.');
        $this->assertObject($x->parse('10'));
        $this->assertObject($x->parse('0x5a'));
        $this->assertObject($x->parse('255'));
        $this->assertFalse($x->parse('256'));
        $this->assertFalse($x->parse('0b101'));
        $this->assertFalse($x->parse('051'));

        //if we have range with leading 0 then we require leading 0
        $x = new Parser('start :=> 01..31.');
        $this->assertFalse($x->parse('5'));
        $this->assertObject($x->parse('05'));

        $x = new Parser('start :=> 1..31 .');
        $this->assertFalse($x->parse('05'));
        $this->assertObject($x->parse('5'));

        $x = new Parser('start :=> 0..31 .');
        $this->assertFalse($x->parse('05'));
        $this->assertObject($x->parse('5'));

        // option switcher "/" turn off all autooptions
        $x = new Parser('start :=> 01..31/d .');
        $this->assertFalse($x->parse('05'));
        $this->assertObject($x->parse('5'));

        // "/" turn off even decimal format
        $x = new Parser('start :=> 0..32/h .');
        $this->assertObject($x->parse('0x5'));
        $this->assertFalse($x->parse('0'));
        $this->assertFalse($x->parse('20'));
    }
}