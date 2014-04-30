<?php

namespace ParserGenerator\Tests\Extension;

use ParserGenerator\Parser;

class StringTest extends \PHPUnit_Framework_TestCase {

    protected function assertObject($a)
    {
        $this->assertTrue(is_object($a));
    }

    public function testSimple() {
        $x = new Parser('start :=> STRING.');

        $this->assertObject($x->parse('"asd\\" "'));
        $this->assertObject($x->parse("'asd\\' '"));
        $this->assertFalse($x->parse('"asd"" "'));

        $x = new Parser('start :=> STRING/apostrophe.');

        $this->assertObject($x->parse("'asd\\' '"));
        $this->assertFalse($x->parse('"asd\\" "'));
        $this->assertObject($x->parse("'asd\\' '"));
        $this->assertFalse($x->parse('"asd"" "'));

        $x = new Parser('start :=> STRING/quotation.');

        $this->assertObject($x->parse('"asd\\" "'));
        $this->assertFalse($x->parse("'asd\\' '"));
        $this->assertFalse($x->parse('"asd"" "'));

        $x = new Parser('start :=> STRING/simple.');

        $this->assertFalse($x->parse('"asd\\" "'));
        $this->assertFalse($x->parse("'asd\\' '"));
        $this->assertObject($x->parse('"asd"" "'));

        $parsingResult = $x->parse('"ab""c"');
        $this->assertEquals('ab"c', $parsingResult->getSubnode(0)->getValue());

        $parsingResult = $x->parse('"\t\n"');
        $this->assertEquals('\t\n', $parsingResult->getSubnode(0)->getValue());
    }
}
