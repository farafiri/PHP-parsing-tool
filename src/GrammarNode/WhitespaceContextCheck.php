<?php

namespace ParserGenerator\GrammarNode;

class WhitespaceContextCheck extends BaseNode implements LeafInterface
{
    protected $char;

    /* this schoul be const but PHP don't accept array as const */
    static protected $whiteCharacters = array(" ", "\n", "\t", "\r");

    public function __construct($char)
    {
        $this->char = $char;
    }

    public function rparse($string, $fromIndex = 0, $restrictedEnd = array())
    {
        $substring = substr($string, $fromIndex, 2);
        if ($substring === "\r\n") {
            $stringChar = "\n";
            $offset = 2;
        } else {
            $stringChar = substr($string, $fromIndex, 1);
            $substring = $stringChar;
            if ($stringChar === "\r") {
                $stringChar = "\n";
            }
            $offset = 1;
        }

        if (($this->char === null) ? in_array($stringChar, self::$whiteCharacters,
            true) : ($this->char === $stringChar)) {
            if (!isset($restrictedEnd[$fromIndex + $offset])) {
                return array(
                    'node' => new \ParserGenerator\SyntaxTreeNode\Leaf($substring),
                    'offset' => $fromIndex + $offset
                );
            }
        }

        if (!isset($restrictedEnd[$fromIndex])) {
            $index = $fromIndex;
            while (--$index >= 0 && in_array(substr($string, $index, 1), self::$whiteCharacters, true)) {
                $char = substr($string, $index, 1);
                if ($char === "\r") {
                    $char = "\n";
                }
                if ($this->char === null || $char === $this->char) {
                    return array('node' => new \ParserGenerator\SyntaxTreeNode\Leaf(''), 'offset' => $fromIndex);
                }
            }

            if ($index < 0) {
                return array('node' => new \ParserGenerator\SyntaxTreeNode\Leaf(''), 'offset' => $fromIndex);
            }
        }

        return false;
    }

    public function __toString()
    {
        return (string) $this->char;
    }
}
