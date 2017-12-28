<?php declare(strict_types=1);

namespace ParserGenerator\Examples;

use ParserGenerator\Parser;

/**
 * Parses a language like:
 * - `foo or bar`
 * - `foo and (bar and (baz or not faz))`
 */
class BooleanExpressionParser extends Parser
{
    public function __construct()
    {
        $grammar = $this->getGrammar();

        parent::__construct($grammar, [
            'caseInsensitive' => true,
            'ignoreWhitespaces' => true,
        ]);
    }

    public function getGrammar(): string
    {
        return <<<'GRAMMAR'
start               :=> exprOr.

tokenKeyword        :=> /[^\s"'()]+/.
tokenAnd            :=> 'and'.
tokenOr             :=> 'or'.
tokenNot            :=> 'not'.

exprOr              :=> exprAnd (tokenOr exprAnd)*.
exprAnd             :=> expr (tokenAnd expr)*.
expr                :=> tokenNot? (tokenKeyword | string)
                    :=> tokenNot? '(' exprOr ')'.
GRAMMAR;
    }
}
