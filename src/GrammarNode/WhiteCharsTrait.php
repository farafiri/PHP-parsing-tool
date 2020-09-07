<?php

namespace ParserGenerator\GrammarNode;

trait WhiteCharsTrait
{
    protected $eatWhiteChars;
    protected $whiteCharsRegex;
    
    public function setEatWhiteChars($eatWhiteChars)
    {
        $this->eatWhiteChars = $eatWhiteChars;
        $this->whiteCharsRegex = '/' . WhiteCharsHelper::getRegex($eatWhiteChars) . '/';
    }
}
