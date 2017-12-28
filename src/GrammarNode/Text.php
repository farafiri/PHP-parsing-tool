<?php declare(strict_types=1);

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

    public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
    {
        if (substr($string, $fromIndex, strlen($this->str)) === $this->str ||
            ($this->str === '' && strlen($string) === $fromIndex)) {
            $endPos = $fromIndex + strlen($this->str);
            if (!isset($restrictedEnd[$endPos])) {
                return ['node' => new \ParserGenerator\SyntaxTreeNode\Leaf($this->str), 'offset' => $endPos];
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