<?php declare(strict_types=1);

namespace ParserGenerator\tests\Examples;

use ParserGenerator\Examples\BooleanExpressionParser;
use PHPUnit\Framework\TestCase;

class BooleanExpressionParserTest extends TestCase
{
    /** @var BooleanExpressionParser */
    protected $parser;

    protected function setUp()
    {
        parent::setUp();

        $this->parser = new BooleanExpressionParser();
    }

    /**
     * @param string $input
     * @dataProvider dataForParseSuccessful
     */
    public function testParseSuccessful(string $input)
    {
        $result = $this->parser->parse($input);

        $this->assertNotFalse($result);
    }

    public function dataForParseSuccessful(): array
    {
        return [
            ['foo'],
            ['foo or bar'],
            ['"foo" or \'bar\''],
            ['foo or (bar and not baz)'],
            ['foo and bar'],
            ['foo\nor\n\tbar'],
            ['not foo or bar'],
            ['not foo and bar'],
            ['not (foo or bar)'],
            ['(a or (b or (c or (d or (e or f) and g))))'],
            ['foo and not bar'],
            ['foo OR NOT bar'],
            ['foo OR "NOT bar"'],
            ['"foo or" and bar'],
            ['"foo and (bar or baz)" or faz'],
            ['; or :'],
            ['{ or }'],
        ];
    }

    /**
     * @param string $input
     * @param string $expectedError
     * @dataProvider dataForParseFail
     */
    public function testParseFail(string $input, string $expectedError)
    {
        $result = $this->parser->parse($input);

        $this->assertFalse($result);

        $this->assertSame($expectedError, $this->parser->getException($input)->getMessage());
    }

    public function dataForParseFail(): array
    {
        return [
            [
                'input' => 'foo or',
                'error' => "line: 1, character: 7\nexpected: (tokenKeyword|string) or /\(/\nfound: ",
            ],
            [
                'input' => '"foo or',
                'error' => "line: 1, character: 1\nexpected: (tokenKeyword|string) or /\(/\nfound: \"foo or",
            ],
            [
                'input' => 'foo and (bar or baz',
                'error' => "line: 1, character: 20\nexpected: /\)/ or (tokenAnd expr)\nfound: ",
            ],
            [
                'input' => 'foo not bar',
                'error' => "line: 1, character: 5\nexpected: (tokenAnd expr) or (tokenOr exprAnd)\nfound: not bar",
            ],
        ];
    }
}
