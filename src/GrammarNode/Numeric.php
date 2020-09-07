<?php declare(strict_types=1);
//TODO: now this class supports only integers : add real support

namespace ParserGenerator\GrammarNode;

use ParserGenerator\Exception;

class Numeric extends \ParserGenerator\GrammarNode\BaseNode implements \ParserGenerator\GrammarNode\LeafInterface
{
    protected $regexes;

    protected $min = null;
    protected $max = null;
    protected $requireFixedCharacters = 0;
    protected $allowFixedCharacters = false;
    protected $formatDec = true;
    protected $formatHex = false;
    protected $formatOct = false;
    protected $formatBin = false;
    protected $eatWhiteChars = false;

    public function __construct($options = [])
    {
        foreach ($options as $key => $value) {
            if (in_array($key, ['min', 'max', 'requireFixedCharacters'], true)) {
                if (is_int($value)) {
                    $this->$key = $value;
                }
            }
            if (in_array($key,
                ['formatDec', 'formatHex', 'formatOct', 'formatBin', 'allowFixedCharacters'],
                true)) {
                if (is_bool($value)) {
                    $this->$key = $value;
                }
            }
            
            $this->eatWhiteChars = $options['eatWhiteChars'] ?? '';
        }

        if (!$this->formatDec && !$this->formatHex && !$this->formatOct && !$this->formatBin) {
            throw new Exception('You must specify at least one proper format');
        }

        if ($this->formatOct && $this->formatDec && ($this->requireFixedCharacters || $this->allowFixedCharacters)) {
            throw new Exception('options fixedCharacters and oct format canot be mixed together');
        }

        $this->buildRegexes();
    }

    protected function buildRegexes()
    {
        $this->regexes = [];

        if ($this->formatHex) {
            $this->regexes[16] = $this->buildRegexForBaseFormat('1-9a-fA-F', '0x');
        }

        if ($this->formatBin) {
            $this->regexes[2] = $this->buildRegexForBaseFormat('1', '0b');
        }

        if ($this->formatOct) {
            $this->regexes[8] = $this->buildRegexForBaseFormat('1-7', '0');
        }

        if ($this->formatDec) {
            $this->regexes[10] = $this->buildRegexForBaseFormat('1-9', '');
        }
    }

    protected function buildRegexForBaseFormat($charSet, $prefix)
    {
        return '/(' . $this->buildSubRegexForBaseFormat($charSet, $prefix) . ')?' . WhiteCharsHelper::getRegex($this->eatWhiteChars) . '/';
    }

    protected function buildSubRegexForBaseFormat($charSet, $prefix)
    {
        if ($this->requireFixedCharacters > 0) {
            return '-?' . $prefix . '[0' . $charSet . ']{' . $this->requireFixedCharacters . '}';
        } else {
            if ($this->allowFixedCharacters) {
                return '-?' . $prefix . '[0' . $charSet . ']+';
            } else {
                return '-?' . $prefix . '([' . $charSet . '][0' . $charSet . ']*|0)';
            }
        }
    }

    public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
    {
        foreach ($this->regexes as $base => $regex) {
            if (preg_match($regex, $string, $match, 0, $fromIndex)) {
                if (isset($match[1])) {
                    $offset = strlen($match[$this->eatWhiteChars ? 0 : 1]) + $fromIndex;
                    if (!isset($restrictedEnd[$offset])) {
                        $value = intval(str_replace(['0x', '0b'], ['', ''], $match[1]), $base);
                        if (isset($this->min) && $value < $this->min) {
                            return false;
                        };
                        if (isset($this->max) && $value > $this->max) {
                            return false;
                        };

                        $node = new \ParserGenerator\SyntaxTreeNode\Numeric($match[1], $base);
                        $node->setAfterContent(substr($match[0], strlen($match[1])));
                        return ['node' => $node, 'offset' => $offset];
                    }
                }
            }
        }

        return false;
    }

    public function __toString()
    {
        $modifiers = $this->requireFixedCharacters ? $this->requireFixedCharacters : '';
        $modifiers .= $this->formatBin ? 'b' : '';
        $modifiers .= $this->formatHex ? 'h' : '';
        $modifiers .= $this->formatOct ? 'o' : '';
        $modifiers .= $this->formatDec ? 'd' : '';
        $modifiers = $modifiers == 'd' ? '' : ('/' . $modifiers);
        return (($this->min === null) ? '-inf' : $this->min) . '..' . (($this->max === null) ? 'inf' : $this->max) . $modifiers;
    }
    
    public function setEatWhiteChars($eatWhiteChars)
    {
        $this->eatWhiteChars = $eatWhiteChars;
        $this->buildRegexes();
    }
}
