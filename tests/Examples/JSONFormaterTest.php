<?php

class JSONFormaterTest extends PHPUnit_Framework_TestCase
{
    public function testSetIndention()
    {
        $formater = new \ParserGenerator\Examples\JSONFormater();

        $jsonTree = $formater->parse('{"a":23,"b":false}');
        $formater->setIndention($jsonTree);

        $nl = "\n";
        $expected = '{' . $nl .
            '    "a": 23,' . $nl .
            '    "b": false' . $nl .
            '}';

        $this->assertEquals($expected, $jsonTree->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_ORIGINAL));

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

        $this->assertEquals($expected, $jsonTree->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_ORIGINAL));
    }
}
