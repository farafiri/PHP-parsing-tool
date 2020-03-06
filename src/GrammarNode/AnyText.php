<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

class AnyText extends \ParserGenerator\GrammarNode\BaseNode implements \ParserGenerator\GrammarNode\LeafInterface
{
    public static $whiteChars = ["\n" => true, "\t" => true, "\r" => true, " " => true];
    public $ignoreWhitespaces;

    public function __construct($options = [])
    {
        $this->ignoreWhitespaces = true;
    }

    public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
    {
        $endPos = $this->getNextNonrestrictedIndex($string, $fromIndex, $restrictedEnd);
        $str = substr($string, $fromIndex, $endPos - $fromIndex);

        if ($endPos !== null) {
            if ($this->ignoreWhitespaces) {
                $trimedString = rtrim($str);
                $whitespaces = substr($str, strlen($trimedString));
                return [
                    'node' => new \ParserGenerator\SyntaxTreeNode\Leaf($trimedString, $whitespaces),
                    'offset' => $endPos,
                ];
            } else {
                return ['node' => new \ParserGenerator\SyntaxTreeNode\Leaf($str), 'offset' => $endPos];
            }
        } else {
            return false;
        }
    }

    protected function getNextNonrestrictedIndex($string, $fromIndex, $restrictedEnd)
    {
        if (!isset($string[$fromIndex])) {
            return isset($restrictedEnd[$fromIndex]) ? null : $fromIndex;
        }

        $i = $fromIndex;
        while (isset($restrictedEnd[$i]) || ($this->ignoreWhitespaces && isset(self::$whiteChars[$string[$i]]))) {
            $i++;
            if (!isset($string[$i])) {
                return isset($restrictedEnd[$i]) ? null : $i;
            }
        }

        return $i;
    }
    
    public function __toString() {
        return 'text';
    }
}
