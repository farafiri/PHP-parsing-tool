<?php

namespace ParserGenerator\Tests\Extension;

use ParserGenerator\GrammarNode;
use ParserGenerator\Parser;
use PHPUnit\Framework\TestCase;
use ParserGenerator\SyntaxTreeNode\Leaf;

class OptionsBranchesTest extends TestCase
{
    
    protected function assertObject($a)
    {
        $this->assertTrue(is_object($a));
    }

    public function testBasic()
    {
        $x = new Parser('start :=> "a" test.', ['nodes' => ['test' => new GrammarNode\Text("b")]]);

        $this->assertObject($x->parse("ab"));
        $this->assertFalse($x->parse("ac"));
        $this->assertFalse($x->parse("a"));
        $this->assertFalse($x->parse("abc"));
    }
    
    public function testClosure()
    {
        $x = new Parser('start :=> "a" anyCharacter'
                . '            :=> anyCharacter "a" anyCharacter.', ['nodes' => ['anyCharacter' => $this->anyCharacterCallback()]]);

        $this->assertObject($x->parse("ab"));
        $this->assertObject($x->parse("ac"));
        $this->assertFalse($x->parse("a"));
        $this->assertFalse($x->parse("abc"));
        $this->assertContains('expected: anyCharacter', (string) $x->getException());
        
        $this->assertObject($x->parse("bab"));
        $this->assertFalse($x->parse("bbb"));
        $this->assertContains('expected: "a"', (string) $x->getException());
    }
    
    public function anyCharacterCallback()
    {
        return function ($string, $index) {
            if (isset($string[$index])) {
                return ['node' => new Leaf($string[$index]), 'offset' => $index + 1]; 
            } else {
                return false;
            }
        };
    }
    
    public function testParametric()
    {
        $x = new Parser('start :=> "a" double<"bx">'
                . '            :=> "a" double<z>'
                . '            :=> "a" double<anyCharacter>.'
                . '      z     :=> "by" .', ['nodes' => ['anyCharacter' => $this->anyCharacterCallback(), 'double' => $this->getDouble()]]);

        $this->assertObject($x->parse("abxbx"));
        $this->assertFalse($x->parse("abxb"));
        $this->assertFalse($x->parse("abxbxx"));
        
        $this->assertObject($x->parse("abyby"));
        $this->assertFalse($x->parse("abyb"));
        $this->assertFalse($x->parse("abybyy"));
        
        $this->assertObject($x->parse("att"));
        $this->assertFalse($x->parse("at"));  
        $this->assertContains('expected: double<"bx"> or double<z> or double<anyCharacter>', (string) $x->getException());
    }
    
    public function getDouble()
    {
        return new class extends \ParserGenerator\NodeFactory {
            function getNode($params, $parser): GrammarNode\NodeInterface {
                $node = $params[0];
                $name = $this->getName() . '<' . $node . '>';
                $branch = new GrammarNode\Branch($name, $name);
                $branch->setNode([[$node, $node]]);
                $branch->setParser($parser);
                return $branch;
            }
        };
    }
    
    public function getUpperFactory()
    {
        return new class extends \ParserGenerator\NodeFactory {
            function getNode($params, $parser): GrammarNode\NodeInterface {
                $str = $this->getStringFromNode($params[0]);
                return new GrammarNode\Text(strtoupper($str));
            }
        };
    }
    
    public function testParametric2()
    {
        $x = new Parser('start :=> "a" upper<"bx">.', ['nodes' => ['upper' => $this->getUpperFactory()]]);

        $this->assertFalse($x->parse("abx"));
        $this->assertFalse($x->parse("ABX"));
        $this->assertObject($x->parse("aBX"));
        
        $x = new Parser('start :=> "a" upper<bx>.'
                . '      bx    :=> "b" "x".', ['nodes' => ['upper' => $this->getUpperFactory()]]);
        
        $this->assertFalse($x->parse("abx"));
        $this->assertFalse($x->parse("ABX"));
        $this->assertObject($x->parse("aBX"));
        
        $x = new Parser('start :=> "a" upper<("b" ("b" x))>.'
                . '      x    :=> "x".', ['nodes' => ['upper' => $this->getUpperFactory()]]);
        
        $this->assertFalse($x->parse("abbx"));
        $this->assertContains('expected: upper<("b" ("b" x))', (string) $x->getException());
        $this->assertFalse($x->parse("ABBX"));
        $this->assertObject($x->parse("aBBX"));
    }
    
    /**
     * @expectedException \ParserGenerator\Exception
     */
    public function testNested()
    {
        $x = new Parser('start :=> "a" upper<bx>.'
                . '      bx    :=> "b" cx.'
                . '      cx    :=> "c" bx.', ['nodes' => ['upper' => $this->getUpperFactory()]]);
        
        $x->parse("ax");
    }
    
    public function testTextNode()
    {
        $x = new Parser('start :=> "a" textNode<(b "c")>.'
                . '      b     :=> "b".');
        
        $this->assertObject($x->parse('abc'));
        
        $x = new Parser('start :=> "a" textNode<(b "c")>.'
                . '      b     :=> "b".', ['caseInsensitive' => true]);
        
        $this->assertObject($x->parse('abc'));
        $this->assertObject($x->parse('Abc'));
        $this->assertFalse($x->parse('ABc'));
    }
    
    public function testRegexNode()
    {
        $x = new Parser('start :=> "a" regexNode<(b "c")>.'
                . '      b     :=> "b".');
        
        $this->assertObject($x->parse('abc'));
        
        $x = new Parser('start :=> regexNode<(b "c")>.'
                . '      b     :=> /[a-z][0-9]/.');
        
        $this->assertObject($x->parse('a0c'));
        $this->assertObject($x->parse('h4c'));
        $this->assertFalse($x->parse('hhc'));
        
        $x = new Parser('start :=> regexNode<(b "c")>.'
                . '      b     :=> /[a-z]|Z/ z'
                . '            :=> z'
                . '            :=> "X".'
                . '      z     :=> /\d+/.');
        
        $this->assertObject($x->parse('Xc'));
        $this->assertObject($x->parse('a000c'));
        $this->assertObject($x->parse('Z1c'));
        $this->assertFalse($x->parse('ac'));
        $this->assertObject($x->parse('33c'));
        
        $x = new Parser('start :=> regexNode<(b "c")>.'
                . '      b     :=> z++",".'
                . '      z     :=> /\d+/.');
        
        
        $this->assertObject($x->parse('12c'));
        $this->assertObject($x->parse('4,6,7c'));
        $this->assertFalse($x->parse('4,5,c'));
        $this->assertFalse($x->parse('c'));
        $this->assertFalse($x->parse('4,5,'));
        $this->assertFalse($x->parse('4,5'));
        
        $x = new Parser('start :=> regexNode<(b "c")>.'
                . '      b     :=> z**",".'
                . '      z     :=> /\d+/.');
        
        
        $this->assertObject($x->parse('12c'));
        $this->assertObject($x->parse('4,6,7c'));
        $this->assertFalse($x->parse('4,5,c'));
        $this->assertObject($x->parse('c'));
        $this->assertFalse($x->parse('4,5,'));
        $this->assertFalse($x->parse('4,5'));
        
        $x = new Parser('start :=> regexNode<(b "c")>.'
                . '      b     :=> z+.'
                . '      z     :=> /\d+X/.');
        
        
        $this->assertObject($x->parse('12Xc'));
        $this->assertObject($x->parse('4X6X7Xc'));
        $this->assertFalse($x->parse('c'));
        $this->assertFalse($x->parse('4X5X'));
    }
    
    public function testWithParametricNodeAsParam()
    {
        $x = new Parser('start :=> "a" upper<repeat3<d>>.'
                . '      d     :=> "d".'
                . '      repeat3<node> :=> node node node.', ['nodes' => ['upper' => $this->getUpperFactory()]]);

        $this->assertObject($x->parse("aDDD"));
        $this->assertFalse($x->parse("addd"));
        $this->assertFalse($x->parse("aDD"));
        $this->assertContains('expected: upper<repeat3<d>>', (string) $x->getException());
        $this->assertFalse($x->parse("aDDDD"));
        $this->assertFalse($x->parse("abb"));
        $this->assertContains('expected: upper<repeat3<d>>', (string) $x->getException());
    }
    
    public function testInsideParametricNode()
    {
        $x = new Parser('start :=> repeat<"", "a">.'
                . '      repeat<as, a> :=> as upper<as>'
                . '                    :=> ?as repeat<(as a), a>.', ['nodes' => ['upper' => $this->getUpperFactory()]]);

        $this->assertObject($x->parse("aA"));
        $this->assertObject($x->parse("aaaAAA"));
        $this->assertFalse($x->parse("aaaaaa"));
        $this->assertFalse($x->parse("aaaAA"));
        $this->assertFalse($x->parse("aDDDD"));
        $this->assertFalse($x->parse("aaaAAAAA"));
    }
}
