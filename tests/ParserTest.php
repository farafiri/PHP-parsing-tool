<?php

use ParserGenerator\Parser;
use ParserGenerator\SyntaxTreeNode\Branch;
use ParserGenerator\SyntaxTreeNode\Root;
use ParserGenerator\SyntaxTreeNode\Leaf;

require_once('/../src/Parser.php');

class ParserTest extends PHPUnit_Framework_TestCase
{
    protected function assertObject($a)
    {
        $this->assertTrue(is_object($a));
    }

    protected function assertArrayElementsEquals($expected, $actual, $message = '')
    {
        //TODO: proper implementation of this function (all below is a hack)
        if ($expected == array_intersect($expected, $actual)) {
            $this->assertTrue(true);
        } else {
            $this->assertEquals($expected, $actual, $message);
        }
    }

    public function testSuperSimple()
    {
        $x = new Parser(array(
            'start' => array('cat', 'dog')
        ));

        $this->assertEquals(new Root('start', 0, array(new Leaf('cat'))), $x->parse('cat'));
        $this->assertEquals(new Root('start', 1, array(new Leaf('dog'))), $x->parse('dog'));
        $this->assertEquals(false, $x->parse(''));
        $this->assertEquals(false, $x->parse('totalywrong'));
        $this->assertEquals(false, $x->parse(' cat'));
        $this->assertEquals(false, $x->parse('cats'));
        $this->assertEquals(false, $x->parse(' dog'));
        $this->assertEquals(false, $x->parse('dogs'));
        $this->assertEquals(false, $x->parse('dogcat'));
    }

    public function testWithEmptyOption()
    {
        $x = new Parser(array(
            'start' => array('', 'aaa')
        ));

        $this->assertObject($x->parse(''));
        $this->assertObject($x->parse('aaa'));
        $this->assertFalse($x->parse('a'));
    }

    public function testOneNamedNode()
    {

        $x = new Parser(array(
            'start' => array(':animal'),
            'animal' => array('cat', 'dog')
        ));

        $this->assertEquals(new Root('start', 0, array(
            new Branch('animal', 0, array(
                new Leaf('cat')
            ))
        )), $x->parse('cat'));

        $this->assertEquals(new Root('start', 0, array(
            new Branch('animal', 1, array(
                new Leaf('dog')
            ))
        )), $x->parse('dog'));

        $this->assertEquals(false, $x->parse(''));
        $this->assertEquals(false, $x->parse('totalywrong'));
        $this->assertEquals(false, $x->parse(' cat'));
        $this->assertEquals(false, $x->parse('cats'));
        $this->assertEquals(false, $x->parse(' dog'));
        $this->assertEquals(false, $x->parse('dogs'));
        $this->assertEquals(false, $x->parse('dogcat'));
    }

    public function testEndRecursion()
    {
        $x = new Parser(array(
            'start' => array(':bs'),
            'bs' => array(array('b', ':bs'), '')
        ));

        $this->assertEquals(new Root('start', 0, array(
            new Branch('bs', 1, array(
                new Leaf('')
            ))
        )), $x->parse(''));

        $this->assertEquals(new Root('start', 0, array(
            new Branch('bs', 0, array(
                new Leaf('b'),
                new Branch('bs', 1, array(
                    new Leaf('')
                ))
            ))
        )), $x->parse('b'));

        $this->assertEquals(new Root('start', 0, array(
            new Branch('bs', 0, array(
                new Leaf('b'),
                new Branch('bs', 0, array(
                    new Leaf('b'),
                    new Branch('bs', 0, array(
                        new Leaf('b'),
                        new Branch('bs', 1, array(
                            new Leaf('')
                        ))
                    ))
                ))
            ))
        )), $x->parse('bbb'));

        $this->assertEquals(false, $x->parse('bbb-bbb'));
        $this->assertEquals(false, $x->parse('c'));
        //$this->assertEquals(false, $x->parse('b '));
    }

    public function testSimpleStartRecursion()
    {
        $x = new Parser(array(
            'start' => array(':bs'),
            'bs' => array(array(':bs', 'b'), '')
        ));

        $this->assertEquals(new Root('start', 0, array(
            new Branch('bs', 1, array(
                new Leaf('')
            ))
        )), $x->parse(''));

        $this->assertEquals(new Root('start', 0, array(
            new Branch('bs', 0, array(
                new Branch('bs', 1, array(
                    new Leaf('')
                )),
                new Leaf('b')
            ))
        )), $x->parse('b'));

        $this->assertEquals(new Root('start', 0, array(
            new Branch('bs', 0, array(
                new Branch('bs', 0, array(
                    new Branch('bs', 0, array(
                        new Branch('bs', 1, array(
                            new Leaf('')
                        )),
                        new Leaf('b')
                    )),
                    new Leaf('b')
                )),
                new Leaf('b')
            ))
        )), $x->parse('bbb'));

        $this->assertEquals(false, $x->parse('bbb-bbb'));
        $this->assertEquals(false, $x->parse('c'));
        $this->assertEquals(false, $x->parse('bx'));
    }

    public function testMultiNodeStartRecursion()
    {
        $x = new Parser(array(
            'start' => array(':as'),
            'as' => array('', array(':bs', 'a')),
            'bs' => array('', array(':as', 'b'))
        ));

        $this->assertEquals(new Root('start', 0, array(
            new Branch('as', 0, array(
                new Leaf('')
            ))
        )), $x->parse(''));

        $this->assertEquals(false, $x->parse('b'));
        $this->assertEquals(false, $x->parse('ab'));

        $this->assertEquals(new Root('start', 0, array(
            new Branch('as', 1, array(
                new Branch('bs', 0, array(
                    new Leaf('')
                )),
                new Leaf('a')
            ))
        )), $x->parse('a'));

        $this->assertEquals(new Root('start', 0, array(
            new Branch('as', 1, array(
                new Branch('bs', 1, array(
                    new Branch('as', 0, array(
                        new Leaf('')
                    )),
                    new Leaf('b')
                )),
                new Leaf('a')
            ))
        )), $x->parse('ba'));

        $this->assertEquals(false, $x->parse('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'));
        $this->assertEquals(false, $x->parse('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'));
        $this->assertEquals(false, $x->parse('ababababababababababababababababababab'));
    }

    public function testMultiNodeStartRecursionRec()
    {
        $x = new Parser(array(
            'start' => array(':as'),
            'as' => array(array(':bs', 'a'), ''),
            'bs' => array(array(':as', 'b'), '')
        ));

        // TODO: var_dump($x->parse('aba'));
        //var_dump($x->parse('ababababababababababa'));

        $this->assertEquals(new Root('start', 0, array(
            new Branch('as', 0, array(
                new Branch('bs', 0, array(
                    new Branch('as', 1, array(
                        new Leaf('')
                    )),
                    new Leaf('b')
                )),
                new Leaf('a')
            ))
        )), $x->parse('ba'));
    }

    public function testSelectFirstMatch()
    {
        $x = new Parser(array(
            'start' => array(array('sss'), array('a', 'bbb'), array('abb', 'b')),
        ));
        $this->assertEquals(1, $x->parse('abbb')->getDetailType());

        $x = new Parser(array(
            'start' => array(array(':start', 'a'), '', 'aaa')
        ));
        $this->assertEquals(0, $x->parse('aaa')->getDetailType());

        $x = new Parser(array(
            'start' => array(array(':a', ':start'), ''),
            'a' => array('aa', 'aaa', 'a')
        ));
        $this->assertEquals(new Root('start', 0, array(
            new Branch('a', 0, array(new Leaf('aa'))),
            new Branch('start', 0, array(
                new Branch('a', 0, array(new Leaf('aa'))),
                new Branch('start', 0, array(
                    new Branch('a', 2, array(new Leaf('a'))),
                    new Branch('start', 1, array(new Leaf('')))
                ))
            ))
        )), $x->parse('aaaaa'));

        $x = new Parser(array(
            'start' => array(array(':ab', ':ac')),
            'ab' => array(array('a', ':ab'), array('b', ':ab'), ''), //greedy
            'ac' => array(array('a', ':ac'), array('c', ':ac'), '')
        ));
        $r = $x->parse('abbaaaacac');
        $this->assertEquals('abbaaaa', (string)$r->getSubnode(0));
        $this->assertEquals('cac', (string)$r->getSubnode(1));

        $x = new Parser(array(
            'start' => array(array(':ab', ':ac')),
            'ab' => array('', array('a', ':ab'), array('b', ':ab')), //non greedy
            'ac' => array('', array('a', ':ac'), array('c', ':ac'))
        ));
        $r = $x->parse('abbaaaacac');
        $this->assertEquals('abb', (string)$r->getSubnode(0));
        $this->assertEquals('aaaacac', (string)$r->getSubnode(1));
    }

    public function testCheckingPredefinedString()
    {
        $x = new Parser(array(
            'start' => array(array(':string'))
        ));

        $this->assertObject($x->parse("'dddffff'"));
        $this->assertFalse($x->parse("a'dddffff'"));
        $this->assertFalse($x->parse("'dddffff'a"));

        $this->assertObject($x->parse('"dddffff"'));
        $this->assertFalse($x->parse('a"dddffff"'));
        $this->assertFalse($x->parse('"dddffff"a'));

        $this->assertFalse($x->parse("'dddffff\\'"));
        $this->assertObject($x->parse("'dddffff\\t'"));
        $this->assertObject($x->parse("'dddffff\\'dddd'"));
        $this->assertObject($x->parse("'dddffff\\dddd'"));
        $this->assertObject($x->parse("'dddffffdddd\\\\'"));
        $this->assertObject($x->parse("'dddfff\"fdd\"dd\\\\'"));

        $this->assertFalse($x->parse('"dddffff\\"'));
        $this->assertFalse($x->parse('"dddffff\\"'));
        $this->assertObject($x->parse('"dddfff\'fdd\'dd\\\\"'));
    }

    public function testCheckingRegexMatching()
    {
        $x = new Parser(array(
            'start' => array(':/a+b?/')
        ));

        $this->assertObject($x->parse('aaaaaaa'));
        $this->assertObject($x->parse('aaab'));
        $this->assertObject($x->parse('a'));
        $this->assertFalse($x->parse('abb'));
        $this->assertFalse($x->parse('a aaab'));
        $this->assertFalse($x->parse(' aaaa'));

        $x = new Parser(array(
            'start' => array(array(':/(aaa)?/'))
        ));
        $this->assertObject($x->parse('aaa'));
        $this->assertObject($x->parse(''));
        $this->assertFalse($x->parse('a'));
        $this->assertFalse($x->parse('aaav'));
        $this->assertFalse($x->parse('aaaa'));
        $this->assertFalse($x->parse('baaa'));

        $x = new Parser(array(
            'start' => array(':/[abc]+/i')
        ));
        $this->assertObject($x->parse('aaa'));
        $this->assertObject($x->parse('abc'));
        $this->assertObject($x->parse('cAB'));
        $this->assertObject($x->parse('ABc'));
        $this->assertFalse($x->parse('ccXB'));

        $x = new Parser(array(
            'start' => array(array(':/a*/', ':/s?/', 'c'))
        ));
        $this->assertObject($x->parse('aaac'));
        $this->assertObject($x->parse('aaasc'));
        $this->assertObject($x->parse('sc'));
        $this->assertObject($x->parse('c'));

        $x = new Parser(array(
            'start' => array(array(':ab', ':/(?<=ab)[ba]+/')),
            'ab' => array('', array('a', ':ab'), array('b', ':ab'))
        ));
        $this->assertEquals('bbaab', (string)$x->parse('bbaabaa')->getSubnode(0));
        $this->assertEquals('aa', (string)$x->parse('bbaabaa')->getSubnode(1));
        $this->assertFalse($x->parse('baaa'));
    }

    public function testIgnoreWhitespacesOption()
    {
        $on = new Parser(array(
            'start' => array(array('x', ':start'), ''),
        ), array('ignoreWhitespaces' => true));

        $off = new Parser(array(
            'start' => array(array(':animal', ':start'), ':animal'),
            'animal' => array('dog', 'cat', 'cow', 'rat')
        ), array('ignoreWhitespaces' => false));

        $this->assertEquals(new Root('start', 0, array(
            new Leaf('x', "  \n   "),
            new Branch('start', 1, array(
                new Leaf('')
            ))
        ), ' '), $on->parse(" x  \n   "));

        $this->assertFalse($off->parse(" x  \n   "));

        $this->assertEquals(new Root('start', 0, array(
            new Leaf('x', '  '),
            new Branch('start', 0, array(
                new Leaf('x', "  \n   "),
                new Branch('start', 1, array(
                    new Leaf('')
                ))
            ))
        )), $on->parse("x  x  \n   "));

        $this->assertFalse($off->parse("x  x  \n   "));

        $this->assertEquals(new Root('start', 1, array(
            new Leaf('', ''),
        ), ' '), $on->parse(" "));

        $this->assertFalse($off->parse(" "));
    }

    public function testStringGrammarInputSimple()
    {
        $x = new Parser('start:=>x "a".
                         x    :=>"b"');

        $this->assertFalse($x->parse('b'));
        $this->assertFalse($x->parse('a'));
        $this->assertObject($x->parse('ba'));
        $this->assertFalse($x->parse('baa'));
        $this->assertFalse($x->parse('bba'));

        $x = new Parser('start:=>x "a".
                         x    :=>""
                              :=>x "b".');

        $this->assertFalse($x->parse('b'));
        $this->assertObject($x->parse('a'));
        $this->assertObject($x->parse('ba'));
        $this->assertFalse($x->parse('baa'));
        $this->assertObject($x->parse('bba'));
    }

    public function testStringGrammarInputRegex()
    {
        $x = new Parser('start :=> /abc/ "de".');
        $this->assertFalse($x->parse('abc'));
        $this->assertFalse($x->parse('de'));
        $this->assertObject($x->parse('abcde'));

        $x = new Parser('start :=> /abc/ /de/.');
        $this->assertFalse($x->parse('abc'));
        $this->assertFalse($x->parse('de'));
        $this->assertObject($x->parse('abcde'));

        $x = new Parser('start :=> /[0-9]+\\//.');
        $this->assertFalse($x->parse('00'));
        $this->assertFalse($x->parse('/'));
        $this->assertObject($x->parse('12/'));
    }

    public function testStringGrammarPredefinedBranches()
    {
        $x = new Parser('start :=> STRING.');
        $this->assertFalse($x->parse('abc'));
        $this->assertObject($x->parse('""'));
        $this->assertObject($x->parse('"abcd"'));
        $this->assertFalse($x->parse('"abcd"ed'));
        $this->assertObject($x->parse('""'));
    }

    public function testDetailType()
    {
        $x = new Parser('start:a => "a"
                            :b => "b".');

        $this->assertEquals(new Root('start', 'a', array(
            new Leaf('a')
        )), $x->parse('a'));

        $this->assertEquals(new Root('start', 'b', array(
            new Leaf('b')
        )), $x->parse('b'));
    }

    public function testStringGrammarBNFStyle()
    {
        $x = new Parser('start:a:= "a"
                              :b:= /b/.');

        $this->assertEquals(new Root('start', 'a', array(
            new Leaf('a')
        )), $x->parse('a'));

        $this->assertEquals(new Root('start', 'b', array(
            new Leaf('b')
        )), $x->parse('b'));
    }

    public function testStringGrammarIgnoreWhitespacesOption()
    {
        $on = new Parser('start :=> "abc".', array('ignoreWhitespaces' => true));
        $off = new Parser('start :=> "abc".', array('ignoreWhitespaces' => false));
        $this->assertObject($on->parse('abc'));
        $this->assertObject($off->parse('abc'));
        $this->assertObject($on->parse(' abc '));
        $this->assertFalse($off->parse(' abc '));

        $on = new Parser('start :=> /abc/ .', array('ignoreWhitespaces' => true));
        $off = new Parser('start :=> /abc/ .', array('ignoreWhitespaces' => false));
        $this->assertObject($on->parse('abc'));
        $this->assertObject($off->parse('abc'));
        $this->assertObject($on->parse(' abc '));
        $this->assertFalse($off->parse(' abc '));


        $on = new Parser('start :=> STRING.', array('ignoreWhitespaces' => true));
        $off = new Parser('start :=> STRING.', array('ignoreWhitespaces' => false));
        $this->assertObject($on->parse('"a"'));
        $this->assertObject($off->parse('"a"'));
        $this->assertObject($on->parse(' "a" '));
        $this->assertFalse($off->parse(' "a" '));

        $on = new Parser('start :=> "abc" x y /z/. x :=> "c". y :=> /y/ .', array('ignoreWhitespaces' => true));
        $off = new Parser('start :=> "abc" x y /z/. x :=> "c". y :=> /y/ .', array('ignoreWhitespaces' => false));
        $this->assertObject($on->parse('abccyz'));
        $this->assertObject($off->parse('abccyz'));
        $this->assertObject($on->parse(' abc c y z '));
        $this->assertFalse($off->parse(' abc c y z '));
        $this->assertFalse($on->parse(' a bc c y z '));
        $this->assertFalse($off->parse(' a bc c y z '));
    }

    public function testToStringWithIgnoreWhitespaces()
    {
        $x = new Parser('start :=> "ab" "cd" .', array('ignoreWhitespaces' => true));

        $this->assertEquals("abcd", $x->parse("ab   cd")->toString());
        $this->assertEquals("ab   cd", $x->parse("ab   cd")->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_ORIGINAL));
        $this->assertEquals("ab cd", $x->parse("ab   cd")->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_REDUCED_WHITESPACES));

        $this->assertEquals("abcd", $x->parse("ab \n cd ")->toString());
        $this->assertEquals("ab \n cd ", $x->parse("ab \n cd ")->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_ORIGINAL));
        $this->assertEquals("ab\ncd", $x->parse("ab \n cd ")->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_REDUCED_WHITESPACES));

        $x = new Parser('start :=> /ab/ /cd/ .', array('ignoreWhitespaces' => true));

        $this->assertEquals("abcd", $x->parse("ab   cd")->toString());
        $this->assertEquals("ab   cd", $x->parse("ab   cd")->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_ORIGINAL));
        $this->assertEquals("ab cd", $x->parse("ab   cd")->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_REDUCED_WHITESPACES));

        $this->assertEquals("abcd", $x->parse("ab \n cd ")->toString());
        $this->assertEquals("ab \n cd ", $x->parse("ab \n cd ")->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_ORIGINAL));
        $this->assertEquals("ab\ncd", $x->parse("ab \n cd ")->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_REDUCED_WHITESPACES));

        $x = new Parser('start :=> /a/ /b/ .', array('ignoreWhitespaces' => true));
        $this->assertEquals("ab", $x->parse("a   b")->toString());
        $this->assertEquals("a   \nb", $x->parse("a   \nb")->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_ORIGINAL));

        $x = new Parser('start :=> STRING .', array('ignoreWhitespaces' => true));
        $this->assertEquals("'abc'", $x->parse("'abc'  ")->toString());
        $this->assertEquals("'abc'  ", $x->parse("'abc'  ")->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_ORIGINAL));

        $x = new Parser('start :=> /ab/ /cd/ .', array('ignoreWhitespaces' => true));

        $this->assertEquals("abcd", $x->parse(" ab   cd")->toString());
        $this->assertEquals(" ab   cd", $x->parse(" ab   cd")->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_ORIGINAL));
        $this->assertEquals("ab cd", $x->parse(" ab   cd")->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_REDUCED_WHITESPACES));
    }

    public function testStringGrammarWhitespaceCharacters()
    {

        $x = new Parser('start :=> "a" \s "b" .', array('ignoreWhitespaces' => true));
        $this->assertFalse($x->parse('ab'));
        $this->assertFalse($x->parse('ab '));
        $this->assertFalse($x->parse(' ab'));
        $this->assertObject($x->parse('a b'));
        $this->assertObject($x->parse("a\nb"));
        $this->assertObject($x->parse("a   b"));

        $x = new Parser('start :=> "a" SPACE "b" .', array('ignoreWhitespaces' => true));
        $this->assertFalse($x->parse('ab'));
        $this->assertFalse($x->parse('ab '));
        $this->assertFalse($x->parse(' ab'));
        $this->assertObject($x->parse('a b'));
        $this->assertFalse($x->parse("a\nb"));
        $this->assertObject($x->parse("a  \nb"));

        $x = new Parser('start :=> "a" !SPACE "b" .', array('ignoreWhitespaces' => true));
        $this->assertObject($x->parse('ab'));
        $this->assertObject($x->parse('ab '));
        $this->assertObject($x->parse(' ab'));
        $this->assertFalse($x->parse('a b'));
        $this->assertObject($x->parse("a\nb"));
        $this->assertFalse($x->parse("a  \nb"));

        $x = new Parser('start :=> "a" !\s "b" .', array('ignoreWhitespaces' => true));
        $this->assertObject($x->parse('ab'));
        $this->assertObject($x->parse('ab '));
        $this->assertObject($x->parse(' ab'));
        $this->assertFalse($x->parse('a b'));
        $this->assertFalse($x->parse("a\nb"));
        $this->assertFalse($x->parse("a  \nb"));
    }

    public function testCaseInsesitivity()
    {
        $on = new Parser('start :=> "a" /b/ .', array('caseInsensitive' => true));
        $off = new Parser('start :=> "a" /b/ .'); //caseInsensitivity is off by default

        $this->assertObject($on->parse('ab'));
        $this->assertObject($off->parse('ab'));

        $this->assertFalse($on->parse('Cb'));
        $this->assertFalse($on->parse('aC'));

        $this->assertObject($on->parse('Ab'));
        $this->assertFalse($off->parse('Ab'));

        $this->assertObject($on->parse('aB'));
        $this->assertFalse($off->parse('aB'));

        $this->assertObject($on->parse('AB'));
        $this->assertFalse($off->parse('AB'));

        $x = new Parser('start :=> /a/ /b/i .', array('caseInsensitive' => true));
        $this->assertObject($x->parse('ab'));
        $this->assertObject($x->parse('AB'));
    }

    public function testErrorTrack()
    {
        $x = new Parser('start :=> "(" num+"," ")" ("a" | "b").
                         num   :=> /\d+/.');

        $this->assertObject($x->parse('(23,45,6)b'));

        $this->assertFalse($x->parse('23,34)b'));
        $e = $x->getError();
        $this->assertEquals(0, $e['index']);
        $this->assertEquals('start', implode(' | ', $e['expected']));

        $this->assertFalse($x->parse('(,34)b'));
        $e = $x->getError();
        $this->assertEquals(1, $e['index']);
        $this->assertEquals('num', implode(' | ', $e['expected']));

        $this->assertFalse($x->parse('(34b'));
        $e = $x->getError();

        $this->assertEquals(3, $e['index']);
        $this->assertEquals('"," | ")"', implode(' | ', $e['expected']));
    }

    public function testComments()
    {
        $nodeDump = function ($parser) {
            $result = array();
            $parser->iterateOverNodes(function ($node) use (&$result) {
                $explodedClassName = explode('\\', get_class(\ParserGenerator\GrammarNode\Decorator::undecorate($node)));
                $className = $explodedClassName[count($explodedClassName) - 1];
                $result[] = $className . '[' . $node . ']';
            });

            return $result;
        };

        $x = new Parser('start :=> /* some comments */ "a"');
        $this->assertArrayElementsEquals(array('Branch[start]', 'Text["a"]'), $nodeDump($x));

        $x = new Parser('start :=> /** "commented" /c*/ **/ "a"');
        $this->assertArrayElementsEquals(array('Branch[start]', 'Text["a"]'), $nodeDump($x));

        $x = new Parser('start :=> "a"
                         /** some comment here
                          *  multi line comment
                          **/
                               :=> "b".');
        $this->assertArrayElementsEquals(array('Branch[start]', 'Text["a"]', 'Text["b"]'), $nodeDump($x));

        $x = new Parser('start :=> b "a".
                         /** some comment here
                          *  multi line comment
                          **/
                          b    :=> "b".');
        $this->assertArrayElementsEquals(array('Branch[start]', 'Branch[b]', 'Text["a"]', 'Text["b"]'), $nodeDump($x));

        $x = new Parser('/* comment here */
                         start /* comment here */ :=> "a" /* comment here */. /* comment here */');
        $this->assertArrayElementsEquals(array('Branch[start]', 'Text["a"]'), $nodeDump($x));

        $x = new Parser('start :=> "a" /** "b" */ "c" **/ /** "d" /* "e" */ "f" **/');
        $this->assertArrayElementsEquals(array('Branch[start]', 'Text["a"]'), $nodeDump($x));
    }
}