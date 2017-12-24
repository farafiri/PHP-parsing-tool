<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 02.12.13
 * Time: 12:32
 */

use ParserGenerator\SyntaxTreeNode\Branch;
use ParserGenerator\SyntaxTreeNode\Leaf;
use ParserGenerator\SyntaxTreeNode\Numeric;
use ParserGenerator\SyntaxTreeNode\Root;
use PHPUnit\Framework\TestCase;

class BranchTest extends TestCase
{
    public function testCopy()
    {
        $b = new Root('x', 0, array(
            new Leaf('leaf1'),
            new Branch('b1', 0, array(
                new Leaf('leaf1.1'),
                new Leaf('leaf1.2')
            )),
            new Numeric('23', 10)
        ));

        $b->refreshOwners();

        $c = $b->copy();

        $this->assertSame($b, $c->origin);
        $this->assertTrue($c->getSubnode(0) instanceof Leaf);
        $this->assertTrue($c->getSubnode(1) instanceof Branch);
        $this->assertTrue($c->getSubnode(2) instanceof Numeric);
        $this->assertSame($b->getSubnode(0), $c->getSubnode(0)->origin);
        $this->assertSame($b->getSubnode(1), $c->getSubnode(1)->origin);
        $this->assertSame($b->getSubnode(2), $c->getSubnode(2)->origin);
        $this->assertSame($c, $c->getSubnode(0)->owner);
        $this->assertSame($c, $c->getSubnode(1)->owner);
        $this->assertSame($c, $c->getSubnode(2)->owner);

        $this->assertSame($b->getSubnode(1)->getSubnode(0), $c->getSubnode(1)->getSubnode(0)->origin);
        $this->assertSame($b->getSubnode(1)->getSubnode(1), $c->getSubnode(1)->getSubnode(1)->origin);

        $this->assertSame($c->getSubnode(1), $c->getSubnode(1)->getSubnode(0)->owner);
        $this->assertSame($c->getSubnode(1), $c->getSubnode(1)->getSubnode(1)->owner);
    }
}
