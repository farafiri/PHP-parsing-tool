<?php

namespace ParserGenerator\GrammarNode;

class Regex extends \ParserGenerator\GrammarNode\BaseNode Implements \ParserGenerator\GrammarNode\LeafInterface
{
    public $lastMatch = -1;
    public $lastNMatch = -1;

    protected $givenRegex;
    protected $regex;
    protected $eatWhiteChars;
    protected $caseInsensitive;

    public function canBeEmpty()
    {
        switch ($this->givenRegex) {
            case '/if\s*\(/':
            case '/foreach\s*\(/':
            case '/for\s*\(/':
            case '/while\s*\(/':
            case '/(return|break|continue|throw|include|include_once|require|require_once|echo)\s+/':
            case '/(return|break|continue)(\s*;)?/':
            case '/(protected|public|private|static)\s+/':
            case '/\(\s*\)/':
            case '/\((array|object|integer|int|boolean|bool|b|binary|string|real|float|double)\)/':
            case '/array\s*\(/':
            case '/:/':
            case '/::/':
            case '/[A-Za-z_][A-Za-z_0-9]*/':
            case '/\$[A-Za-z_][A-Za-z_0-9]*/':
            case '/-?\d+/':
            case '/-?\d+(\.\d+)?/':
            case '/(true|false)/':
            case '/(\/\*(.|\n)*\*\/|\/\/.*\n|)/':
                return false;
            default:
                return true;
        }
    }

    public function startChars()
    {
        switch ($this->givenRegex) {
            case '/if\s*\(/':
                return array('i' => true);
            case '/foreach\s*\(/':
                return array('f' => true);
            case '/for\s*\(/':
                return array('f' => true);
            case '/while\s*\(/':
                return array('w' => true);
            case '/(return|break|continue|throw|include|include_once|require|require_once|echo)\s+/':
            case '/(return|break|continue)(\s*;)?/':
                return array('r' => true, 'b' => true, 'c' => true, 't' => true, 'i' => true, 'e' => true);
            case '/(protected|public|private|static)\s+/':
                return array('p' => true, 's' => true);
            case '/\(\s*\)/':
            case '/\((array|object|integer|int|boolean|bool|b|binary|string|real|float|double)\)/':
                return array('(' => true);
            case '/array\s*\(/':
                return array('a' => true);
            case '/:/':
            case '/::/' :
                return array(':' => true);
            case '/[A-Za-z_][A-Za-z_0-9]*/':
                $a = array('_' => true);
                for ($i = ord('a'); $i <= ord('z'); $i++) {
                    $a[chr($i)] = true;
                }
                for ($i = ord('A'); $i <= ord('Z'); $i++) {
                    $a[chr($i)] = true;
                }
                return $a;
            case '/\$[A-Za-z_][A-Za-z_0-9]*/':
                return array('$' => true);
            case '/-?\d+/':
            case '/-?\d+(\.\d+)?/':
                return array('-' => true, '0' => true, '1' => true, '2' => true, '3' => true, '4' => true, '5' => true, '6' => true, '7' => true, '8' => true, '9' => true);
            case '/(true|false)/':
                return array('t' => true, 'f' => true);
            case '/(\/\*(.|\n)*\*\/|\/\/.*\n|)/':
                return array('/' => true);
            default:
                return parent::startChars();
        }
    }

    public function __construct($regex, $eatWhiteChars = false, $caseInsensitive = false)
    {
        $this->eatWhiteChars = $eatWhiteChars;
        $this->caseInsensitive = $caseInsensitive;
        $this->givenRegex = $regex;
        if (preg_match('/\/(.*)\/([A-Za-z]*)/s', $regex, $match)) {
            $regexBody = $match[1];
            $regexModifiers = $match[2];
            if (strpos($regexModifiers, 'i') === false && $caseInsensitive) {
                $regexModifiers .= 'i';
            }
            $this->regex = '/(' . $regexBody . ')?\s*/' . $regexModifiers;
        } else {
            throw new Exception ("Wrong regex format [$regex]");
        }
    }

    public function rparse($string, $fromIndex = 0, $restrictedEnd = array())
    {
        if (preg_match($this->regex, $string, $match, 0, $fromIndex)) {
            if (isset($match[1])) {
                $offset = strlen($match[$this->eatWhiteChars ? 0 : 1]) + $fromIndex;
                if (!isset($restrictedEnd[$offset])) {
                    $node = new \ParserGenerator\SyntaxTreeNode\Leaf($match[1], $this->eatWhiteChars ? substr($match[0], strlen($match[1])) : '');

                    if ($this->lastMatch < $fromIndex) {
                        $this->lastMatch = $fromIndex;
                    }
                    return array('node' => $node, 'offset' => $offset);
                }
            }
        }

        if ($this->lastNMatch < $fromIndex) {
            $this->lastNMatch = $fromIndex;
        }

        return false;
    }

    public function getRegex()
    {
        return $this->givenRegex;
    }

    public function __toString()
    {
        return $this->givenRegex;
    }
}