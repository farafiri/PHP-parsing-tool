<?php

namespace ParserGenerator\Tests\ParsedNodes;

use PHPUnit\Framework\TestCase;

class ParserNodeTest extends TestCase
{
    public function testClone()
    {
        $a = new \ParserGenerator\SyntaxTreeNode\Branch('a', 'b', [
            new \ParserGenerator\SyntaxTreeNode\Branch('q', 'w', [
                new \ParserGenerator\SyntaxTreeNode\Leaf('l1'),
            ]),
            new \ParserGenerator\SyntaxTreeNode\Leaf('l2'),
        ]);

        $b = clone $a;

        $this->assertTrue($a == $b);
        $this->assertFalse($a === $b);

        $this->assertFalse($a->getSubnode(0) === $b->getSubnode(0));
        $this->assertFalse($a->getSubnode(1) === $b->getSubnode(1));
    }

    public function testToString()
    {
        $a = new \ParserGenerator\SyntaxTreeNode\Branch('a', 'b', [
            new \ParserGenerator\SyntaxTreeNode\Branch('q', 'w', [
                new \ParserGenerator\SyntaxTreeNode\Leaf('l1'),
            ]),
            new \ParserGenerator\SyntaxTreeNode\Leaf('l2'),
        ]);

        $this->assertEquals('l1l2', (string)$a);

        $a->getSubnode(0)->setSubnode(null, new \ParserGenerator\SyntaxTreeNode\Leaf('l3'));
        $this->assertEquals('l1l3l2', (string)$a);

        $a->setSubnode(null, new \ParserGenerator\SyntaxTreeNode\Leaf('l4'));
        $this->assertEquals('l1l3l2l4', (string)$a);

        $a->setSubnode(1, new \ParserGenerator\SyntaxTreeNode\Leaf('l5'));
        $this->assertEquals('l1l3l5l4', (string)$a);

        $a->setSubnode(2, clone $a);
        $this->assertEquals('l1l3l5l1l3l5l4', (string)$a);
    }

    public function testCompare()
    {
        $a = new \ParserGenerator\SyntaxTreeNode\Branch('a', 'b', [
            new \ParserGenerator\SyntaxTreeNode\Branch('q', 'w', [
                new \ParserGenerator\SyntaxTreeNode\Leaf('l1'),
            ]),
            new \ParserGenerator\SyntaxTreeNode\Leaf('l2'),
        ]);

        $b = clone $a;

        $this->assertTrue($a->compare($b));

        $b->getSubnode(0)->setType('qq');

        $this->assertFalse($a->compare($b));
        $this->assertTrue($a->compare($b,
            \ParserGenerator\SyntaxTreeNode\Base::COMPARE_DEFAULT xor \ParserGenerator\SyntaxTreeNode\Base::COMPARE_CHILDREN_NORMAL));

        $b = clone $a;
        $b->setDetailType('');

        $this->assertFalse($a->compare($b));
        $this->assertTrue($a->compare($b,
            \ParserGenerator\SyntaxTreeNode\Base::COMPARE_DEFAULT xor \ParserGenerator\SyntaxTreeNode\Base::COMPARE_SUBTYPE));

        $b = clone $a;
        $b->setType('');

        $this->assertFalse($a->compare($b));
        $this->assertTrue($a->compare($b,
            \ParserGenerator\SyntaxTreeNode\Base::COMPARE_DEFAULT xor \ParserGenerator\SyntaxTreeNode\Base::COMPARE_TYPE));

        $b = clone $a;
        $b->getSubnode(0)->getSubnode(0)->setContent('lx');

        $this->assertFalse($a->compare($b));
        $this->assertTrue($a->compare($b,
            \ParserGenerator\SyntaxTreeNode\Base::COMPARE_DEFAULT xor \ParserGenerator\SyntaxTreeNode\Base::COMPARE_LEAF));
    }

    protected function getTestNode1()
    {
        return new \ParserGenerator\SyntaxTreeNode\Branch('a', '', [
            new \ParserGenerator\SyntaxTreeNode\Branch('b', '', [
                new \ParserGenerator\SyntaxTreeNode\Leaf('l1'),
                new \ParserGenerator\SyntaxTreeNode\Branch('b', '', [
                    new \ParserGenerator\SyntaxTreeNode\Branch('c', '', [
                        new \ParserGenerator\SyntaxTreeNode\Leaf('l2'),
                    ]),
                    new \ParserGenerator\SyntaxTreeNode\Branch('b', '', [
                        new \ParserGenerator\SyntaxTreeNode\Leaf('l3'),
                        new \ParserGenerator\SyntaxTreeNode\Leaf('l4'),
                    ]),
                ]),
            ]),
            new \ParserGenerator\SyntaxTreeNode\Leaf('l2'),
            new \ParserGenerator\SyntaxTreeNode\Branch('c', '', [
                new \ParserGenerator\SyntaxTreeNode\Branch('b', '', []),
                new \ParserGenerator\SyntaxTreeNode\Branch('d', '', [
                    new \ParserGenerator\SyntaxTreeNode\Leaf('l5'),
                ]),
            ]),
        ]);
    }

    public function testFindAll()
    {
        $a = $this->getTestNode1();

        $this->assertEquals([$a], $a->findAll('a'));

        $this->assertEquals([
            $a->getSubnode(0)->getSubnode(1)->getSubnode(0),
            $a->getSubnode(2),
        ], $a->findAll('c'));

        $this->assertEquals([
            $a->getSubnode(2)->getSubnode(1),
        ], $a->findAll('d'));

        $this->assertEquals([], $a->findAll('nonExistingType'));
        $this->assertEquals([], $a->findAll('nonExistingType', true));
        $this->assertEquals([], $a->findAll('nonExistingType', true, true));

        $this->assertEquals([
            $a->getSubnode(0),
            $a->getSubnode(2)->getSubnode(0),
        ], $a->findAll('b'));

        $this->assertEquals([
            $a->getSubnode(0),
            $a->getSubnode(0)->getSubnode(1),
            $a->getSubnode(0)->getSubnode(1)->getSubnode(1),
            $a->getSubnode(2)->getSubnode(0),
        ], $a->findAll('b', true));

        $this->assertEquals([
            $a->getSubnode(0)->getSubnode(1)->getSubnode(1),
            $a->getSubnode(0)->getSubnode(1),
            $a->getSubnode(0),
            $a->getSubnode(2)->getSubnode(0),
        ], $a->findAll('b', true, true));
    }
}
