<?php

namespace ParserGenerator\Tests\Extension\ItemRestrictions;

use ParserGenerator\Extension\ItemRestrictions\Is;
use ParserGenerator\Parser;
use PHPUnit\Framework\TestCase;

class IsTest extends TestCase
{
    public function test()
    {
        $x = new Parser("start :=> 'abcd'
                               :=> 'ab'.");

        $contain = new Is($x->grammar['start']);

        $this->assertFalse($contain->check('qwerty', 0, 3, null));
        $x->cache = [];
        $this->assertTrue($contain->check('xabcd', 1, 5, null));
        $x->cache = [];
        $this->assertFalse($contain->check('xabcf', 1, 5, null));
        $x->cache = [];
        $this->assertTrue($contain->check('xabcf', 1, 3, null));
        $x->cache = [];
        $this->assertFalse($contain->check('xabcd', 1, 4, null));
        $x->cache = [];
        $this->assertTrue($contain->check('ab', 0, 2, null));
        $x->cache = [];
        $this->assertTrue($contain->check('ab  ', 0, 2, null));
        $x->cache = [];

        $x = new Parser("start :=> 'abcd'
                               :=> 'ab'.", ['ignoreWhitespaces' => true]);

        $contain = new Is($x->grammar['start']);

        $this->assertTrue($contain->check('ab  ', 0, 4, null));

        // I dont realy know what to return in these cases
        //$x->cache = array();
        //$this->assertTrue($contain->check('ab  ', 0, 3, null));
        //$x->cache = array();
        //$this->assertTrue($contain->check('ab  ', 0, 2, null));
    }
}
