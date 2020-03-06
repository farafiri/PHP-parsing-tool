<?php

namespace ParserGenerator\Tests;

use ParserGenerator\Parser;
use ParserGenerator\Backtracer;
use PHPUnit\Framework\TestCase;

class BacktracerTest extends TestCase {
    
    protected function getBacktraces($grammar, $string, $index, $onlyFirstAtIndex = true, $foldSamePaths = true)
    {
        $backtracer = new Backtracer($index);
        $parser = new \ParserGenerator\Parser($grammar, ['backtracer' => $backtracer]);
        $parser->parse($string);

        $result = [];
        foreach ($backtracer->getTraces($onlyFirstAtIndex, $foldSamePaths) as $trace) {
            $result[] = $trace['index'] . ' ' . $trace['node'];
        }

        return implode(', ', $result);
    }

    public function testBase()
    {
        $grammar = 'start :=> "a" "a" "c"'
                . '      :=> "aa" "d".';

        $this->assertEquals('0 start, 2 "c"', $this->getBacktraces($grammar, 'aac', 2));
        $this->assertEquals('0 start, 2 "c", 2 "d"', $this->getBacktraces($grammar, 'aan', 2));
        $this->assertEquals('0 start, 2 "c"', $this->getBacktraces($grammar, 'aac', 2, true, false));
        $this->assertEquals('0 start, 2 "c", 0 start, 2 "d"', $this->getBacktraces($grammar, 'aad', 2, true, false));

        $grammar = 'start :=> "aa" cd.'
                . 'cd    :=> "c"'
                . '      :=> "d".';

        $this->assertEquals('0 start, 2 cd', $this->getBacktraces($grammar, 'aac', 2));
        $this->assertEquals('0 start, 2 cd', $this->getBacktraces($grammar, 'aan', 2));
        $this->assertEquals('0 start, 2 cd', $this->getBacktraces($grammar, 'aac', 2, true, false));
        $this->assertEquals('0 start, 2 cd, 0 start, 2 cd', $this->getBacktraces($grammar, 'aad', 2, true, false));
        $this->assertEquals('0 start, 2 cd, 2 "c"', $this->getBacktraces($grammar, 'aac', 2, false, true));
        $this->assertEquals('0 start, 2 cd, 2 "c", 2 "d"', $this->getBacktraces($grammar, 'aan', 2, false, true));
        $this->assertEquals('0 start, 2 cd, 2 "c", 0 start, 2 cd, 2 "d"', $this->getBacktraces($grammar, 'aan', 2, false, false));

        $grammar = 'start :=> "aa" xcd'
                . '      :=> "a" acd.'
                . 'acd   :=> "a" cd.'
                . 'xcd   :=> cd.'
                . 'cd    :=> "c"'
                . '      :=> "d".';

        $this->assertEquals('0 start, 1 acd, 2 cd, 2 xcd', $this->getBacktraces($grammar, 'aan', 2));

        $grammar = 'start :=> expression.'
                . 'expression :=> simplexpression "+" expression'
                . '           :=> simplexpression.'
                . 'simplexpression :=> /\d+/'
                . '                :=> "(" expression ")".';

        $this->assertEquals('0 start, 0 expression, 2 expression, 4 expression', $this->getBacktraces($grammar, '1+2+3', 4));
        $this->assertEquals('0 start, 0 expression, 2 expression, 3 "+"', $this->getBacktraces($grammar, '1+2+3', 3));
        $this->assertEquals('0 start, 0 expression, 0 simplexpression, 4 ")", 1 expression, 3 expression, 4 "+"', $this->getBacktraces($grammar, '(1+2+3', 4));
        $this->assertEquals('0 start, 0 expression, 0 simplexpression, 1 expression, 3 expression, 3 simplexpression, 5 ")", 4 expression, 5 "+"', $this->getBacktraces($grammar, '(1+(2+3', 5));
        $this->assertEquals('0 start, 0 expression, 2 expression, 2 simplexpression, 2 /\d+/', $this->getBacktraces($grammar, '1+2+3', 2, false)); //there is no check for ( because digit matches
        $this->assertEquals('0 start, 0 expression, 2 expression, 2 simplexpression, 2 "(", 2 /\d+/', $this->getBacktraces($grammar, '1+b+3', 2, false));

        $grammar = 'start :=> expression.'
                . 'expression :=> expression "+" simplexpression'
                . '           :=> simplexpression.'
                . 'simplexpression :=> /\d+/'
                . '                :=> "(" expression ")".';

        $this->assertEquals('0 start, 0 expression, 4 simplexpression', $this->getBacktraces($grammar, '1+2+3', 4));
        $this->assertEquals('0 start, 0 expression, 3 "+"', $this->getBacktraces($grammar, '1+2+3', 3));
        $this->assertEquals('0 start, 0 expression, 0 simplexpression, 4 ")", 1 expression, 4 "+"', $this->getBacktraces($grammar, '(1+2+3', 4));
        $this->assertEquals('0 start, 0 expression, 0 simplexpression, 1 expression, 3 simplexpression, 5 ")", 4 expression, 5 "+"', $this->getBacktraces($grammar, '(1+(2+3', 5));
    }

    public function testOtherNodes()
    {
        $grammar = 'start :=> "b" /[a-z]/ string 1..100.';

        $this->assertEquals('0 start, 1 /[a-z]/', $this->getBacktraces($grammar, 'ba"n"t', 1));
        $this->assertEquals('0 start, 2 string', $this->getBacktraces($grammar, 'ba"n"t', 2));
        $this->assertEquals('0 start, 5 1..100', $this->getBacktraces($grammar, 'ba"n"t', 5));

        $grammar = 'start :=> "a" unorder("", "a", /[A-Z]/, string, 1..100).';

        $this->assertEquals('0 start, 1 unorder', $this->getBacktraces($grammar, 'ax', 1));
        $this->assertEquals('0 start, 1 unorder, 1 "a", 1 /[A-Z]/, 1 1..100, 1 string', $this->getBacktraces($grammar, 'ax', 1, false));
        $this->assertEquals('0 start, 1 unorder, 3 1..100, 3 string', $this->getBacktraces($grammar, 'aaAx', 3)); // "a" and /[A-Z]/ already matched
        $this->assertEquals('0 start, 1 unorder, 6 "a"', $this->getBacktraces($grammar, 'a"a"B5x', 6));
    }

    public function testSeries()
    {
        $grammar = 'start :=> "b" acd.'
                . 'acd   :=> "a"+("c"?) "d".';

        $this->assertEquals('0 start, 1 acd, 1 "a"+("c"?), 2 "a", 2 "c", 2 "d"', $this->getBacktraces($grammar, 'bax', 2));
        $this->assertEquals('0 start, 1 acd, 1 "a"+("c"?), 3 "a"', $this->getBacktraces($grammar, 'bacx', 3));
        $this->assertEquals('0 start, 1 acd', $this->getBacktraces($grammar, 'bx', 1));
        $this->assertEquals('0 start, 1 acd, 1 "a"+("c"?), 1 "a"', $this->getBacktraces($grammar, 'bx', 1, false));

        $grammar = 'start :=> "b" acd.'
                . 'acd   :=> "a"+"c" "d".';

        $this->assertEquals('0 start, 1 acd, 1 "a"+"c", 2 "c", 2 "d"', $this->getBacktraces($grammar, 'bax', 2));
        $this->assertEquals('0 start, 1 acd, 1 "a"+"c", 3 "a"', $this->getBacktraces($grammar, 'bacx', 3));
        $this->assertEquals('0 start, 1 acd', $this->getBacktraces($grammar, 'bx', 1));
        $this->assertEquals('0 start, 1 acd, 1 "a"+"c", 1 "a"', $this->getBacktraces($grammar, 'bx', 1, false));

        $grammar = 'start :=> "b" acd.'
                . 'acd   :=> "a"*"c" "d".';

        $this->assertEquals('0 start, 1 acd, 1 "a"*"c", 2 "c", 2 "d"', $this->getBacktraces($grammar, 'bax', 2));
        $this->assertEquals('0 start, 1 acd, 1 "a"*"c", 3 "a"', $this->getBacktraces($grammar, 'bacx', 3));
        $this->assertEquals('0 start, 1 acd', $this->getBacktraces($grammar, 'bx', 1));
        $this->assertEquals('0 start, 1 acd, 1 "a"*"c", 1 "a", 1 "d"', $this->getBacktraces($grammar, 'bx', 1, false));
        
        $grammar = 'start :=> "b" a+";".'
                 . 'a     :=> /[a-z]+/+",".';
        
        $this->assertEquals('0 start, 1 a+";", 4 ";", 3 a, 3 /[a-z]+/+",", 4 ","', $this->getBacktraces($grammar, 'ba;vQ', 4));
    }
    
    public function testContextCheck()
    {
        $grammar = 'start :=> "b" ?a b.'
                 . 'a     :=> "a" "a".'
                 . 'b     :=> /[a-z]/ /[a-z]/.';
        
        $this->assertEquals('0 start, 1 a, 2 "a", 1 b, 2 /[a-z]/', $this->getBacktraces($grammar, 'baa', 2));
        
        $grammar = 'start :=> "b" !a b.'
                 . 'a     :=> "a" "a".'
                 . 'b     :=> /[a-z]/ /[a-z]/.';

        $this->assertEquals('0 start, 1 b, 2 /[a-z]/', $this->getBacktraces($grammar, 'bab', 2)); //hide negative lookaround
        
        $grammar = 'start :=> "a" text not contain "a" and is b.'
                 . 'b     :=> "b" "c".';
        
        $this->assertEquals('0 start, 1 text, 1 b, 2 "c"', $this->getBacktraces($grammar, 'abc', 2)); //hide negative check
    }
}
