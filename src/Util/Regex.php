<?php declare(strict_types=1);

namespace ParserGenerator\Util;

use Exception;

class Regex
{
    protected static $specialChars = ['\\', '/', '+', '*', '?', '[', ']', '(', ')', '|', '.', '$', '^', '{', '}'];
    protected static $instance = 0;
    protected $parser = null;


    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function buildRegexFromString($str)
    {
        $translateTo = array_map(function ($a) {
            return '\\' . $a;
        }, static::$specialChars);
        $regex = '/' . str_replace(static::$specialChars, $translateTo, $str) . '/';
        return $regex;
    }
    
    /**
     * Reverts buildRegexFromString
     * i.e. buildStringFromRegex(buildRegexFromString($str)) == $str
     * 
     * @param string $regex
     * @return string|null
     */
    public static function buildStringFromRegex(string $regex)
    {
        $result = '';
        
        for($i = 1; isset($regex[$i]); $i++) {
            if ($regex[$i] == '\\') {
                $result .= $regex[++$i];
            } elseif ($regex[$i] == '/') {
                return $result;
            } elseif(in_array($regex[$i], static::$specialChars)) {
                return null;
            } else {
                $result .= $regex[$i];
            }
        }
    }

    public static function getGrammarArray()
    {
        return [
            'start' => [['/', ':regex', '/', ':regexModifiers']],
            'regexModifiers' => [':/[imsxeADSUXJu]*/'],
            'regex' => [[':regexLine', '|', ':regex'], ':regexLine'],
            'regexLine' => [[':regexToken', ':regexLine'], ''],
            'regexToken' => [
                ':startMatcher',
                ':endMatcher',
                ':lookAround',
                [':singleStatement', ':repetition', ':nonGreedy'],
            ],
            'lookAround' => [['(?', ':lookArroundType', ':regex', ')']],
            'lookArroundType' => ['=', '!', '<=', '<!'],
            'singleStatement' => [
                ['(?:', ':regex', ')'],
                ['(', ':regex', ')'],
                ['[', ':characterSet', ']'],
                ['[^', ':characterSet', ']'],
                ['.'],
                [':character'],
            ],
            'characterSet' => [
                [':simpleCharacter', '-', ':simpleCharacter', ':characterSet'],
                [':character', ':characterSet'],
                '',
            ],
            'character' => [[':/\\\\./'], ':simpleCharacter'],
            'simpleCharacter' => [':/[^\\\\\\]\\[\\+\\*\\?\\|\\(\\)\\$\\^\\/]/'],
            'repetition' => [
                '?',
                '+',
                '*',
                ['{', ':/\d+/', '}'],
                ['{', ':/\d+/', ',', ':/\d*/', '}'],
                '',
            ],
            'nonGreedy' => ['?', ''],
            'startMatcher' => ['^'],
            'endMatcher' => ['$'],
        ];
    }

    public function getParser()
    {
        if (empty($this->parser)) {
            $this->parser = new \ParserGenerator\Parser(static::getGrammarArray());
        }
        return $this->parser;
    }

    public function setParser($parser)
    {
        $this->parser = $parser;
    }

    /**
     * @throws Exception
     */
    public function canBeEmpty($regex)
    {
        if (is_string($regex)) {
            $regex = $this->getParser()->parse($regex);

            if (empty($regex)) {
                throw new Exception('Invalid argument, [string isn\'t valid regular expression]');
            }
        }

        if ($regex instanceof \ParserGenerator\SyntaxTreeNode\Branch) {
            if ($regex->getType() === 'start') {
                $regex = $regex->getSubnode(1);
            }

            return $this->_canBeEmpty($regex);
        }

        throw new Exception('invalid argument given [epected string or \ParserGenerator\SyntaxTreeNode\Branch]');
    }

    protected function _canBeEmpty($node)
    {
        switch ($node->getType()) {
            case 'regex':
                return $this->_canBeEmpty($node->getSubnode(0)) || $node->getSubnode(2) && $this->_canBeEmpty($node->getSubnode(2));

            case 'regexLine':
                return ($node->getDetailType() === 1) || ($this->_canBeEmpty($node->getSubnode(0)) && $this->_canBeEmpty($node->getSubnode(1)));

            case 'regexToken':
                return ($node->getDetailType() !== 3) || $this->possibleZeroRepetition($node->getSubnode(1)) || $this->_canBeEmpty($node->getSubnode(0));

            case 'singleStatement':
                return ($node->getDetailType() === 0 || $node->getDetailType() === 1) && $this->_canBeEmpty($node->getSubnode(1));

            default:
                return true;
        }
    }

    protected function possibleZeroRepetition($node)
    {
        if ($node->getDetailType() === 0 || $node->getDetailType() === 2) {
            return true;
        } elseif ($node->getDetailType() === 1 || $node->getDetailType() === 5) {
            return false;
        } else {
            return 0 === (int)(string)$node->getSubnode(1);
        }
    }

    public function getStartCharacters($regex)
    {
        if (is_string($regex)) {
            $regex = $this->getParser()->parse($regex);

            if (empty($regex)) {
                throw new Exception('Invalid argument, [string isn\'t valid regular expression]');
            }
        }

        if ($regex instanceof \ParserGenerator\SyntaxTreeNode\Branch) {
            if ($regex->getType() === 'start') {
                $regex = $regex->getSubnode(1);
            }

            return $this->_getStartCharacters($regex);
        }

        throw new Exception('invalid argument given [epected string or \ParserGenerator\SyntaxTreeNode\Branch]');
    }

    protected function _getStartCharacters($node)
    {
        switch ($node->getType()) {
            case 'regex':
                return $this->_getStartCharacters($node->getSubnode(0)) + ($node->getSubnode(2) ? $this->_getStartCharacters($node->getSubnode(2)) : []);

            case 'regexLine':
                if ($node->getDetailType() === 0) {
                    if ($this->_canBeEmpty($node->getSubnode(0))) {
                        return $this->_getStartCharacters($node->getSubnode(0)) + $this->_getStartCharacters($node->getSubnode(1));
                    } else {
                        return $this->_getStartCharacters($node->getSubnode(0));
                    }
                } elseif ($node->getDetailType() === 1) {
                    return [];
                }

            case 'regexToken':
                return ($node->getDetailType() !== 3) ? [] : $this->_getStartCharacters($node->getSubnode(0));

            case 'singleStatement':
                if ($node->getDetailType() === 0 || $node->getDetailType() === 1 || $node->getDetailType() === 2) {
                    return $this->_getStartCharacters($node->getSubnode(1));
                } elseif ($node->getDetailType() === 3) {
                    return $this->getReverseCharSet($this->_getStartCharacters($node->getSubnode(1)));
                } elseif ($node->getDetailType() === 5) {
                    return $this->_getStartCharacters($node->getSubnode(0));
                } elseif ($node->getDetailType() === 4) {
                    return $this->getReverseCharSet(["\n"]);
                }

            case 'character':
                if ($node->getDetailType() === 0) {
                    switch ((string)$node) {
                        case '\\s':
                            return ["\n" => true, "\r" => true, " " => true, "\t" => true];
                        case '\\d':
                            return $this->getCharacterRange('0', '9');
                        case '\\w':
                            return;
                        case '\\[':
                        case '\\]':
                        case '\\(':
                        case '\\)':
                        case '\\*':
                        case '\\+':
                        case '\\?':
                        case '\\|':
                        case '\\/':
                        case '\\\\':
                        case '\\{':
                        case '\\.':
                        case '\\$':
                        case '\\^':
                            $result = [];
                            $result[substr((string)$node, 1, 1)] = true;
                            return $result;
                        default:
                            throw new Exception("Unknown character group [$node]");
                    }
                } else {
                    $result = [];
                    $result[(string)$node] = true;
                    return $result;
                }

            case 'characterSet': //array(array(':simpleCharacter', '-', ':simpleCharacter', ':characterSet'), array(':character', ':characterSet'), ''),
                switch ($node->getDetailType()) {
                    case 0:
                        if (!$node->getSubnode(2)) {
                            print_r($node);
                        };
                        return $this->getCharacterRange((string)$node->getSubnode(0),
                                (string)$node->getSubnode(2)) + $this->_getStartCharacters($node->getSubnode(3));
                    case 1:
                        return $this->_getStartCharacters($node->getSubnode(0)) + $this->_getStartCharacters($node->getSubnode(1));
                    case 2:
                        return [];
                }
            default:
                return true;
        }
    }

    protected function getReverseCharSet($charSet)
    {
        $result = [];
        for ($i = 0; $i < 256; $i++) {
            if (empty($charSet[chr($i)])) {
                $result[chr($i)] = true;
            }
        }

        return $result;
    }

    protected function getCharacterRange($from, $to)
    {
        $result = [];
        for ($i = ord((string)$from); $i <= ord((string)$to); $i++) {
            $result[chr($i)] = true;
        }

        return $result;
    }

    public function generateString($regex)
    {
        if (is_string($regex)) {
            $regex = $this->getParser()->parse($regex);

            if (empty($regex)) {
                throw new Exception('Invalid argument, [string isn\'t valid regular expression]');
            }
        }

        if ($regex instanceof \ParserGenerator\SyntaxTreeNode\Branch) {
            if ($regex->getType() === 'start') {
                $regex = $regex->getSubnode(1);
            }

            return $this->_generateString($regex);
        }

        throw new Exception('invalid argument given [epected string or \ParserGenerator\SyntaxTreeNode\Branch]');
    }

    public function getOccurenceRange($node)
    {
        //'repetition' => array('?', '+', '*', array('{', ':/\d+/', '}'), array('{', ':/\d+/', ',', ':/\d*/', '}'), '');
        switch ($node->getDetailType()) {
            case 0:
                return ['min' => 0, 'max' => 1];
            case 1:
                return ['min' => 1];
            case 2:
                return ['min' => 0];
            case 3:
                $o = (int)(string)$node->getSubnode(1);
                return ['min' => $o, 'max' => $o];
            case 4:
                $min = (int)(string)$node->getSubnode(1);
                $maxStr = (string)$node->getSubnode(3);
                $max = $maxStr ? (int)$maxStr : null;
                return ['min' => $min, 'max' => $max];
        }
        return ['min' => 1, 'max' => 1];
    }

    protected function _generateString($node)
    {
        switch ($node->getType()) {
            case 'regex':
                if ($node->getDetailType() === 0) {
                    return rand(0,
                        1) ? $this->_generateString($node->getSubnode(0)) : $this->_generateString($node->getSubnode(2));
                } else {
                    return $this->_generateString($node->getSubnode(0));
                }

            case 'regexLine':
                if ($node->getDetailType() === 0) {
                    return $this->_generateString($node->getSubnode(0)) . $this->_generateString($node->getSubnode(1));
                } else {
                    return '';
                }
            case 'regexToken':
                switch ($node->getDetailType()) {
                    case 0:
                    case 1:
                        return '';
                    case 2:
                        throw new Exception("canot generate regex string with lookaround");
                    case 3:
                        $occurenceRange = $this->getOccurenceRange($node->getSubnode(1));
                        if (empty($occurenceRange['max'])) {
                            $occurence = $occurenceRange['min'];
                            while (rand(0, 2)) {
                                $occurence++;
                            }
                        } else {
                            $occurence = rand($occurenceRange['min'], $occurenceRange['max']);
                        }

                        $result = '';
                        for ($i = 0; $i < $occurence; $i++) {
                            $result .= $this->_generateString($node->getSubnode(0));
                        }
                        return $result;
                }

            case 'singleStatement':
                switch ($node->getDetailType()) {
                    case 0:
                    case 1:
                        return $this->_generateString($node->getSubnode(1));
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                        return array_rand($this->_getStartCharacters($node));
                }

            default:
                throw new Exception('this code should be never executed');
        }
    }
}
