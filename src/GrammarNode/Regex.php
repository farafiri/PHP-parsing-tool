<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

use ParserGenerator\Exception;

class Regex extends \ParserGenerator\GrammarNode\BaseNode Implements \ParserGenerator\GrammarNode\LeafInterface
{
    public $lastMatch = -1;
    public $lastNMatch = -1;

    //$giveText is for nodes created from string node
    protected $givenText;
    protected $givenRegex;
    protected $regex;
    protected $eatWhiteChars;
    protected $caseInsensitive;

    public function __construct($regex, $eatWhiteChars = false, $caseInsensitive = false, $givenText = null) 
    {
        $this->givenText = $givenText;
        $this->eatWhiteChars = $eatWhiteChars;
        $this->caseInsensitive = $caseInsensitive;
        $this->givenRegex = $regex;
        $this->prepare();
    }

    public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
    {
        if (preg_match($this->regex, $string, $match, 0, $fromIndex)) {
            if (isset($match[1])) {
                $offset = strlen($match[$this->eatWhiteChars ? 0 : 1]) + $fromIndex;
                if (!isset($restrictedEnd[$offset])) {
                    $node = new \ParserGenerator\SyntaxTreeNode\Leaf($match[1],
                        $this->eatWhiteChars ? substr($match[0], strlen($match[1])) : '');

                    if ($this->lastMatch < $fromIndex) {
                        $this->lastMatch = $fromIndex;
                    }
                    return ['node' => $node, 'offset' => $offset];
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
    
    public function getText()
    {
        return $this->givenText;
    }

    public function __toString()
    {
        if ($this->givenText === null) {
            return $this->givenRegex;
        } else {
            return '"' . addslashes($this->givenText) . '"';
        }
    }
    
    protected function prepare()
    {
        $regex = $this->givenRegex;
        if (preg_match('/\/(.*)\/([A-Za-z]*)/s', $regex, $match)) {
            $regexBody = $match[1];
            $regexModifiers = $match[2];
            if (strpos($regexModifiers, 'i') === false && $this->caseInsensitive) {
                $regexModifiers .= 'i';
            }
            $this->regex = '/(' . $regexBody . ')?' . WhiteCharsHelper::getRegex($this->eatWhiteChars) . '/' . $regexModifiers;
        } else {
            throw new Exception("Wrong regex format [$regex]");
        }
    }
    
    public function setEatWhiteChars($eatWhiteChars)
    {
        $this->eatWhiteChars = $eatWhiteChars;
        $this->prepare();
    }
}
