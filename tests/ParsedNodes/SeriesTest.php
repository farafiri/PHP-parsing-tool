<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Rafał
 * Date: 22.04.13
 * Time: 11:28
 * To change this template use File | Settings | File Templates.
 */

namespace ParserGenerator\Tests\ParsedNodes;

use ParserGenerator\Parser;
use PHPUnit\Framework\TestCase;

class SeriesTest extends TestCase
{
    public function testGetMainNodes()
    {
        $x = new Parser('start :=> /\d+/+",".');
        //var_Dump($x->parse('8,12,4'));

        $seriesNode = $x->parse('8,12,4')->getSubnode(0);

        $this->assertTrue($seriesNode instanceof \ParserGenerator\SyntaxTreeNode\Series);
        $this->assertEquals(array(
            new \ParserGenerator\SyntaxTreeNode\Leaf('8'),
            new \ParserGenerator\SyntaxTreeNode\Leaf('12'),
            new \ParserGenerator\SyntaxTreeNode\Leaf('4')
        ), $seriesNode->getMainNodes());
    }

    public function testOrderBy()
    {
        $x = new Parser('start :=> /\d+/+",".');
        $seriesNode = $x->parse('8,12,4')->getSubnode(0);

        $seriesNode->orderBy();

        $this->assertEquals('4,8,12', (string)$seriesNode);
    }
}
