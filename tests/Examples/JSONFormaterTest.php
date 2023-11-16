<?php

namespace ParserGenerator\Tests\Examples;

use ParserGenerator\Examples\JSONFormater;
use ParserGenerator\SyntaxTreeNode\Base;
use PHPUnit\Framework\TestCase;

class JSONFormaterTest extends TestCase
{
    public function testSetIndention()
    {
        $formater = new JSONFormater();

        $jsonTree = $formater->parse('{"a":23,"b":false}');
        $formater->setIndention($jsonTree);

        $nl = "\n";
        $expected = '{' . $nl .
            '    "a": 23,' . $nl .
            '    "b": false' . $nl .
            '}';

        $this->assertEquals($expected, $jsonTree->toString(Base::TO_STRING_ORIGINAL));

        $jsonTree = $formater->parse('[{"a":0, "b":34}, {"x":17}]');
        $formater->setIndention($jsonTree);

        $expected = '[' . $nl .
            '    {' . $nl .
            '        "a": 0,' . $nl .
            '        "b": 34' . $nl .
            '    },' . $nl .
            '    {' . $nl .
            '        "x": 17' . $nl .
            '    }' . $nl .
            ']';

        $this->assertEquals($expected, $jsonTree->toString(Base::TO_STRING_ORIGINAL));
    }
}
