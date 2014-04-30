<?php

namespace ParserGenerator\GrammarNode;

class Text extends \ParserGenerator\GrammarNode\BaseNode implements \ParserGenerator\GrammarNode\LeafInterface
{
    public $lastMatch = -1;
    public $lastNMatch = -1;

    protected $str = '';

    public function __construct($str)
    {
        $this->str = $str;
    }

    public function canBeEmpty()
    {
        return strlen($this->str) === 0;
    }

    public function startChars()
    {
        if (strlen($this->str)) {
            $r = array();
            $r[substr($this->str, 0, 1)] = true;

            return $r;
        } else {
            return array();
        }
    }

    public function rparse($string, $fromIndex = 0, $restrictedEnd = array())
    {
        if (substr($string, $fromIndex, strlen($this->str)) == $this->str) {
            $endPos = $fromIndex + strlen($this->str);
            if (!isset($restrictedEnd[$endPos])) {
                return array('node' => new \ParserGenerator\SyntaxTreeNode\Leaf($this->str), 'offset' => $endPos);
            }
        }

        return false;
    }

    public function getString()
    {
        return $this->str;
    }

    public function __toString()
    {
        return '"' . addslashes($this->str) . '"';
    }
}