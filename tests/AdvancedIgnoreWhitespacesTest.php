<?php

namespace ParserGenerator\Tests;

use ParserGenerator\Parser;
use ParserGenerator\SyntaxTreeNode\Branch;
use ParserGenerator\SyntaxTreeNode\Leaf;
use ParserGenerator\SyntaxTreeNode\Root;
use PHPUnit\Framework\TestCase;

class AdvancedIgnoreWhitespacesTest extends TestCase 
{
    protected function assertObject($a)
    {
        $this->assertTrue(is_object($a));
    }
    
    public function getCombinations()
    {
        $cc = '(\s|\/\*(.|\s)*?\*\/)*';
        return [
            [false, '', true],
            [true,  '', true],
            ['',    '', true],
            ['\s*', '', true],
            ['_*',  '', true],
            [$cc,   '', true],
            [false, '  ', false],
            [true,  '  ', true],
            [true,  " \n ", true],
            ['',    '  ', false],
            ['\s*', '  ', true],
            [$cc,   '  ', true],
            [false, ' /*comment*/', false],
            [true,  ' /*comment*/', false],
            ['_*',  '/*comment*/', false],
            [$cc,   ' /*comment*/', true],
            [false, '__', false],
            [true,  '__', false],
            ['_*',  '__', true],
            ['_*',  ' ', false],
            [$cc,   '__', false],
            ['[[:blank:]]*', " \t ", true],
            ['[[:blank:]]*', " \n ", false],
        ];
    }
    
    /**
     * @dataProvider getCombinations
     */
    public function testCustomWhitespacesString($ignoreWhitespaces, $wsString, $expected)
    {
        $grammarStr = 'start :=> "a" "z"'
                . '          :=> /[bc]+/ "z"'
                . '          :=> string "z"'
                . '          :=> string/simple "z"'
                . '          :=> 1..100 "z"'
                . '          :=> time(Y-m-d) "z".';
         
        $x       = new Parser($grammarStr, ['ignoreWhitespaces' => $ignoreWhitespaces]);
        $message = "ignoreWhitespaces:" . print_r($ignoreWhitespaces, true) . " wsString:($wsString) result:" . ($expected ? 'true' : 'false');

        $this->assertEquals($expected, (bool) $x->parse("{$wsString}az"), $message);
        $this->assertEquals($expected, (bool) $x->parse("a{$wsString}z"), $message);
        $this->assertEquals($expected, (bool) $x->parse("bcb{$wsString}z"), $message);
        $this->assertEquals($expected, (bool) $x->parse("'xyz'{$wsString}z"), $message);
        $this->assertEquals($expected, (bool) $x->parse("\"xy\"\"z\"{$wsString}z"), $message);
        $this->assertEquals($expected, (bool) $x->parse("34{$wsString}z"), $message);
        $this->assertEquals($expected, (bool) $x->parse("2020-05-06{$wsString}z"), $message);
    }
    
    public function testCustomWhitespacesString2()
    {
        //this is simmilar case as last block in testStringGrammarIgnoreWhitespacesOption but with ['ignoreWhitespaces' => '_*']
        //instead of ['ignoreWhitespaces' => true]
        $x = new Parser('start :=> "abc" x y /z/. x :=> "c". y :=> /y/ .', ['ignoreWhitespaces' => '_*']);
        $this->assertObject($x->parse('abccyz'));
        $this->assertObject($x->parse('_abc_c_y_z_'));
        $this->assertFalse($x->parse('_ab_c_c_y_z_'));
        $this->assertObject($x->parse('abc___cy_z__'));
        
        $x = new Parser('start :=> "abc" x y /z/. x :=> "c". y :=> /y/ .', ['ignoreWhitespaces' => '(\s|\/\*(.|\s)*?\*\/)*']);
        $this->assertObject($x->parse('abccyz'));
        $this->assertObject($x->parse(' abc c y z'));
        $this->assertObject($x->parse('/* comment 1 */abc c/*comment*/y z /* comment */'));
        $this->assertObject($x->parse("/*comment line 1\ncomment line 2\r\ncomment line 3*/abc /*another \r multiline comment*/ cy/* comment */ /* comment */ /* cm*/ z"));
        $this->assertFalse($x->parse(' ab/*comment*/c c y z'));
    }
    
    public function testSetIgnoreWhitespacesBase()
    {
        $x = new Parser('start :=> "a" setIgnoreWhitespaces<("b" "c"|"d" "f"), true> "x".', ['ignoreWhitespaces' => false]);
        $this->assertObject($x->parse("abcx"));
        $this->assertObject($x->parse("ab cx"));
        $this->assertObject($x->parse("ad fx"));
        $this->assertFalse($x->parse("a bcx"));
        
        $x = new Parser('start :=> "a" setIgnoreWhitespaces<node, true> "x".'
                . '      node  :=> "b" "c"'
                . '            :=> "d" "f"'
                . '            :=> "r" node.', ['ignoreWhitespaces' => false]);
        $this->assertObject($x->parse("abcx"));
        $this->assertObject($x->parse("ab cx"));
        $this->assertObject($x->parse("ad fx"));
        $this->assertObject($x->parse("ar rr rd fx"));
        $this->assertFalse($x->parse("a bcx"));
        
        $x = new Parser('start :=> "a" setIgnoreWhitespaces<node, /\s*/> "x".'
                . '      node  :=> "b" "c"'
                . '            :=> "d" "f"'
                . '            :=> "r" node.', ['ignoreWhitespaces' => false]);
        $this->assertObject($x->parse("ar rr rd fx"));
        $this->assertFalse($x->parse("a bcx"));
        
        $x = new Parser('start :=> "a" setIgnoreWhitespaces<("b" "c"|"d" "f"), false> "x".', ['ignoreWhitespaces' => true]);
        $this->assertObject($x->parse("abcx"));
        $this->assertObject($x->parse("a bcx"));
        $this->assertObject($x->parse("a dfx"));
        $this->assertFalse($x->parse("ab cx"));
        
        $x = new Parser('start :=> "a" setIgnoreWhitespaces<node, false> "x".'
                . '      node  :=> "b" "c"'
                . '            :=> "d" "f"'
                . '            :=> "r" node.', ['ignoreWhitespaces' => true]);
        $this->assertObject($x->parse("abcx"));
        $this->assertObject($x->parse("a bcx"));
        $this->assertObject($x->parse("a dfx"));
        $this->assertFalse($x->parse("ab cx"));
        $this->assertObject($x->parse("arrrrdfx"));
        $this->assertFalse($x->parse("arrr rdfx"));
    }
    
    /**
     * @dataProvider getCombinations
     */
    public function testSetIgnoreWhitespacesAllLeafNodeTypes($ignoreWhitespaces, $wsString, $expected)
    {
        if ($ignoreWhitespaces === '') {
            $ignoreWhitespaces = false; // empty regex raises parse error
        }
        
        $setting = is_bool($ignoreWhitespaces) ? ($ignoreWhitespaces ? 'true' : 'false') : ('/' . $ignoreWhitespaces . '/');
        $grammarStr = 'start :=> "" setIgnoreWhitespaces<node, ' . $setting . '>.'
                . '    node  :=> "a" "z"'
                . '          :=> /[bc]+/ "z"'
                . '          :=> string "z"'
                . '          :=> string/simple "z"'
                . '          :=> 1..100 "z"'
                . '          :=> time(Y-m-d) "z".';
         
        $x       = new Parser($grammarStr, ['ignoreWhitespaces' => false]);
        $message = "ignoreWhitespaces:" . print_r($ignoreWhitespaces, true) . " wsString:($wsString) result:" . ($expected ? 'true' : 'false');

        $this->assertEquals($expected, (bool) $x->parse("a{$wsString}z"), $message);
        $this->assertEquals($expected, (bool) $x->parse("bcb{$wsString}z"), $message);
        $this->assertEquals($expected, (bool) $x->parse("'xyz'{$wsString}z"), $message);
        $this->assertEquals($expected, (bool) $x->parse("\"xy\"\"z\"{$wsString}z"), $message);
        $this->assertEquals($expected, (bool) $x->parse("34{$wsString}z"), $message);
        $this->assertEquals($expected, (bool) $x->parse("2020-05-06{$wsString}z"), $message);
    }
    
    public function testSetIgnoreWhitespacesVariousNodes()
    {
        $x = new Parser('
            start :=> "x" setIgnoreWhitespaces<(a "y")?, true> "z".
            a     :=> "a" b "a".
            b     :=> "b" "b".
                ', ['ignoreWhitespaces' => false]);
        
        $this->assertObject($x->parse("xz"));
        $this->assertObject($x->parse("xabbayz"));
        $this->assertObject($x->parse("xa b b a yz"));
        
        
        $x = new Parser('
            start :=> "x" setIgnoreWhitespaces<a<"y">, true> "z".
            a<x>  :=> "a" x "a".
                ', ['ignoreWhitespaces' => false]);
       
        $this->assertObject($x->parse("xayaz"));
        $this->assertObject($x->parse("xay az"));//params should be updated
        $this->assertObject($x->parse("xa yaz"));//parametrized node should be changed
        
        $x = new Parser('
            start :=> "x" setIgnoreWhitespaces<a<("y" | b<"u">)>, true> "z".
            a<x>  :=> "a" b<x> "a" x.
            b<x>  :=> "b" x "b".
                ', ['ignoreWhitespaces' => false]);
        
        $this->assertObject($x->parse("xabybayz"));
        $this->assertObject($x->parse("xabbubbabubz"));
        $this->assertObject($x->parse("xa byba yz"));
        $this->assertObject($x->parse("xa b y b a yz"));//same as 2 lines up but with spaces
        $this->assertObject($x->parse("xa b b u b b a b u bz"));//same as 2 lines up but with spaces
        
        $x = new Parser('start           :=> setIgnoreWhitespaces<list<"", "x">, true>.
                         list<x, e>      :=> ?x list<(x e), e>
                                         :=> x+",".');
        
        $this->assertObject($x->parse("xxxx,xxxx"));
        $this->assertFalse($x->parse("xxxx,xxxxx"));
        $this->assertObject($x->parse("x x xx,xxx x"));
        $this->assertObject($x->parse("xxxx,xx  xx"));
        $this->assertFalse($x->parse("xxxx,x  x"));
    }
    
    public function testSetIgnoreWhitespacesPreserveCase()
    {
        $x = new Parser('
            start :=> "x" setIgnoreWhitespaces<a, true> "z".
            a     :=> textNode<"a", false, true> "b" textNode<"c", false, true> "d".');

        $this->assertObject($x->parse("xabcdz"));
        $this->assertObject($x->parse("xa b c dz"));
        $this->assertObject($x->parse("xAbCdz"));
        $this->assertFalse($x->parse("xaBcdz"));
        $this->assertFalse($x->parse("xabcDz"));
        
        $x = new Parser('
            start :=> "x" setIgnoreWhitespaces<a, true> "z".
            a     :=> regexNode</a|regexa/, false, true> /b|regexb/ regexNode</c/, false, true> /d/.');
        
        $this->assertObject($x->parse("xabcdz"));
        $this->assertObject($x->parse("xa b c dz"));
        $this->assertObject($x->parse("xAbCdz"));
        $this->assertFalse($x->parse("xaBcdz"));
        $this->assertFalse($x->parse("xabcDz"));
        
        $this->assertObject($x->parse("xregexa b cdz"));
        $this->assertObject($x->parse("xa regexb cdz"));
        $this->assertObject($x->parse("xrEGEXa b cdz"));
        $this->assertFalse($x->parse("xregexa B cdz"));
    }
    
    public function testOverrideAferContentSimple()
    {
        $x = new Parser('
            start :=> "x" setIgnoreWhitespaces<abc, true> "z".
            abc   :=> "a" "b" "c".');
         
        $this->assertObject($x->parse("xa b cz"));
        $this->assertFalse($x->parse("xa b c z"));
         
        $x = new Parser('
            start :=> "x" setIgnoreWhitespaces<abc, false> "z".
            abc   :=> "a" "b" "c".', ['ignoreWhitespaces' => true]);
         
        $result = $x->parse("x abc  z");
        $this->assertObject($result);
        $this->assertEquals("x abc  z", $result->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_ORIGINAL));
    }
    
    public function testOverrideAferContent()
    {
        $x = new Parser('
            start :=> "x" setIgnoreWhitespaces<abc, true> "z".
            abc   :=> "a" "b"
                  :=> "a" "b" "c" "z"
                  :=> "a" "b" "c".');
         
        $this->assertObject($x->parse("xa b cz"));
        $this->assertFalse($x->parse("xa bccz"));
        $this->assertObject($x->parse("xa bcz"));
        $this->assertFalse($x->parse("xa b c z"));
         
        $x = new Parser('
            start :=> "x" setIgnoreWhitespaces<abc, false> "z".
            abc   :=> "a" "b"
                  :=> "a" "b" "c" "z"
                  :=> "a" "b" "c".', ['ignoreWhitespaces' => true]);
         
        $result = $x->parse("x abc  z");
        $this->assertObject($result);
        $this->assertEquals("x abc  z", $result->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_ORIGINAL));
        
        $result = $x->parse("x abcz");
        $this->assertObject($result);
        $this->assertEquals("x abcz", $result->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_ORIGINAL));
    }
}
