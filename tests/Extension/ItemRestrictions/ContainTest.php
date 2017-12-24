<?php

namespace ParserGenerator\Tests\Extension\ItemRestrictions;

use ParserGenerator\Parser;
use PHPUnit\Framework\TestCase;

class ContainTest extends TestCase
{
    public function test()
    {
        $x = new Parser("start :=> 'abcd'
                               :=> 'ab'.");

        $contain = new \ParserGenerator\Extension\ItemRestrictions\Contain($x->grammar['start']);

        $this->assertFalse($contain->check('qwerty', 0, 3, null));
        $x->cache = array();
        $this->assertTrue($contain->check('abcdef', 0, 6, null));
        $x->cache = array();
        $this->assertTrue($contain->check('aabcdef', 0, 7, null));
        $x->cache = array();
        $this->assertFalse($contain->check('abcdef', 1, 6, null));
        $x->cache = array();
        $this->assertTrue($contain->check('aabcdef', 1, 7, null));
        $x->cache = array();
        $this->assertFalse($contain->check('abcdef', 0, 1, null));
        $x->cache = array();
        $this->assertTrue($contain->check('abcd', 0, 3, null));
        $x->cache = array();
        $this->assertTrue($contain->check('ab', 0, 2, null));
        $x->cache = array();
        $this->assertTrue($contain->check('ab   ', 0, 5, null));

        $x = new Parser("start :=> 'abcd'
                               :=> 'ab'.", array('ignoreWhitespaces' => true));

        $contain = new \ParserGenerator\Extension\ItemRestrictions\Contain($x->grammar['start']);

        $this->assertTrue($contain->check('ab  ', 0, 4, null));
        $x->cache = array();
        $this->assertTrue($contain->check('ab  ', 0, 3, null));
        $x->cache = array();
        $this->assertTrue($contain->check('ab  ', 0, 2, null));
    }
}
