<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

class TextS extends \ParserGenerator\GrammarNode\Text
{
    public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
    {
        if (substr($string, $fromIndex, strlen($this->str)) == $this->str ||
            ($this->str === '' && strlen($string) === $fromIndex)) {
            $endPos = $fromIndex + strlen($this->str);
            preg_match('/\s*/', $string, $match, 0, $endPos);
            $endPos += strlen($match[0]);
            if (!isset($restrictedEnd[$endPos])) {
                $node = new \ParserGenerator\SyntaxTreeNode\Leaf($this->str, $match[0]);
                return ['node' => $node, 'offset' => $endPos];
            }
        }

        return false;
    }
}