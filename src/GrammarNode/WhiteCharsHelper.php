<?php

namespace ParserGenerator\GrammarNode;

/**
 * static class for white characters management
 * mainly used for converting boolean values into white characters regex string
 */
class WhiteCharsHelper
{
    /**
     * @param  string|boolean $eatWhiteChars
     * @return string
     */
    public static function getRegex($eatWhiteChars)
    {
        if ($eatWhiteChars === false) {
            return '';
        } elseif ($eatWhiteChars === true) {
            return '\s*';
        } else {
            return $eatWhiteChars;
        }
    }
}
