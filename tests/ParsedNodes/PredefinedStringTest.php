<?php

namespace ParserGenerator\Tests\ParsedNodes;

use PHPUnit\Framework\TestCase;

class PredefinedStringTest extends TestCase
{

    public function testGetValue()
    {
        $slash = "\\";
        $apo = "'";
        $quot = '"';

        $x = new \ParserGenerator\SyntaxTreeNode\PredefinedString($quot . $slash . $apo . $quot);
        $this->assertEquals($apo, $x->getValue());
        $this->assertEquals($slash . $apo, $x->getPHPValue());

        $x = new \ParserGenerator\SyntaxTreeNode\PredefinedString($quot . $slash . $slash . $quot);
        $this->assertEquals($slash, $x->getValue());
        $this->assertEquals($slash, $x->getPHPValue());

        $x = new \ParserGenerator\SyntaxTreeNode\PredefinedString($quot . $slash . $quot . $quot);
        $this->assertEquals($quot, $x->getValue());
        $this->assertEquals($quot, $x->getPHPValue());

        $x = new \ParserGenerator\SyntaxTreeNode\PredefinedString($quot . $slash . "n" . $quot);
        $this->assertEquals("\n", $x->getValue());
        $this->assertEquals("\n", $x->getPHPValue());

        $x = new \ParserGenerator\SyntaxTreeNode\PredefinedString($quot . $slash . "t" . $quot);
        $this->assertEquals("\t", $x->getValue());
        $this->assertEquals("\t", $x->getPHPValue());


        $x = new \ParserGenerator\SyntaxTreeNode\PredefinedString($apo . $slash . $apo . $apo);
        $this->assertEquals($apo, $x->getValue());
        $this->assertEquals($apo, $x->getPHPValue());

        $x = new \ParserGenerator\SyntaxTreeNode\PredefinedString($apo . $slash . $slash . $apo);
        $this->assertEquals($slash, $x->getValue());
        $this->assertEquals($slash, $x->getPHPValue());

        $x = new \ParserGenerator\SyntaxTreeNode\PredefinedString($apo . $slash . $quot . $apo);
        $this->assertEquals($quot, $x->getValue());
        $this->assertEquals($slash . $quot, $x->getPHPValue());

        $x = new \ParserGenerator\SyntaxTreeNode\PredefinedString($apo . $slash . "n" . $apo);
        $this->assertEquals("\n", $x->getValue());
        $this->assertEquals($slash . "n", $x->getPHPValue());

        $x = new \ParserGenerator\SyntaxTreeNode\PredefinedString($apo . $slash . "t" . $apo);
        $this->assertEquals("\t", $x->getValue());
        $this->assertEquals($slash . "t", $x->getPHPValue());
    }
}
