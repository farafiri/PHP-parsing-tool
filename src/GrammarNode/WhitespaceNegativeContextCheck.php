<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

class WhitespaceNegativeContextCheck extends \ParserGenerator\GrammarNode\BaseNode implements LeafInterface
{
    protected $char;

    /* this schoul be const but PHP don't accept array as const */
    static protected $whiteCharacters = [" ", "\n", "\t", "\r"];

    public function __construct($char)
    {
        $this->char = $char;
    }

    public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
    {
        if (!isset($restrictedEnd[$fromIndex])) {
            $index = $fromIndex;
            while (--$index >= 0 && in_array(substr($string, $index, 1), self::$whiteCharacters, true)) {
                if ($this->char === null || substr($string, $index, 1) === $this->char) {
                    return false;
                }
            }

            return ['node' => new \ParserGenerator\SyntaxTreeNode\Leaf(''), 'offset' => $fromIndex];
        }

        return false;
    }

    public function __toString()
    {
        return (string) $this->char;
    }
}