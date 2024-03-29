<?php

namespace ParserGenerator\Tests\Examples;

use ParserGenerator\Parser;
use PHPUnit\Framework\TestCase;

class PostfixToInfixNotationTranslatorTest extends TestCase
{
    public function translate($str): string
    {
        $parser = new Parser('
            start    :=> start start operator
                     :=> -inf..inf.
            operator :=> "+"
                     :=> "-"
                     :=> "*"
                     :=> "/".
        ', ['ignoreWhitespaces' => true]);

        $tree = $parser->parse($str);

        $tree->inPlaceTranslate('start', function ($node, $parent) {
            if ($node->getDetailType() == 1) {
                return;
            }

            $temp = $node->getSubnode(1);
            $node->setSubnode(1, $node->getSubnode(2));
            $node->setSubnode(2, $temp);

            if ($parent && in_array((string)$node->getSubnode(1),
                    ['+', '-']) && in_array((string)$parent->getSubnode(2), ['*', '/'])) {
                return '(' . $node . ')';
            }
        });

        return $tree->toString();
    }

    public function testTranslator()
    {
        $this->assertEquals('2+3+4', $this->translate('2 3 + 4 +'));
        $this->assertEquals('2+3+4', $this->translate('2 3 4 + +'));
        $this->assertEquals('2+3*4', $this->translate('2 3 4 * +'));
        $this->assertEquals('2*(3+4)', $this->translate('2 3 4 + *'));
        $this->assertEquals('2*(3+4+5*6)', $this->translate('2 3 4 5 6 * + + *'));
    }
}
