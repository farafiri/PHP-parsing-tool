<?php

use ParserGenerator\Parser;

class IsTest extends PHPUnit_Framework_TestCase
{
    public function test()
	{
	    $x = new Parser("start :=> 'abcd'
		                       :=> 'ab'.");
							   
	    $contain = new \ParserGenerator\Extension\ItemRestrictions\Is($x->grammar['start']);
		
		$this->assertFalse($contain->check('qwerty', 0, 3, null));
		$x->cache = array();
		$this->assertTrue($contain->check('xabcd', 1, 5, null));
		$x->cache = array();
		$this->assertFalse($contain->check('xabcf', 1, 5, null));
		$x->cache = array();
		$this->assertTrue($contain->check('xabcf', 1, 3, null));
		$x->cache = array();
		$this->assertFalse($contain->check('xabcd', 1, 4, null));
		$x->cache = array();
		$this->assertTrue($contain->check('ab', 0, 2, null));
		$x->cache = array();
		$this->assertTrue($contain->check('ab  ', 0, 2, null));
		$x->cache = array();
		
		$x = new Parser("start :=> 'abcd'
		                       :=> 'ab'.", array('ignoreWhitespaces' => true));
							   
	    $contain = new \ParserGenerator\Extension\ItemRestrictions\Is($x->grammar['start']);
		
		$this->assertTrue($contain->check('ab  ', 0, 4, null));
		
		// I dont realy know what to return in these cases
		//$x->cache = array();
		//$this->assertTrue($contain->check('ab  ', 0, 3, null));
		//$x->cache = array();
		//$this->assertTrue($contain->check('ab  ', 0, 2, null));
	}
}