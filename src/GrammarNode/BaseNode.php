<?php

namespace ParserGenerator\GrammarNode;

abstract class BaseNode implements \ParserGenerator\GrammarNode\NodeInterface
{
    public function canBeEmpty()
    {
        return true;
    }

    public function startChars()
    {
        $result = array();
        for ($i = 0; $i < 256; $i++) {
            $result[chr($i)] = true;
        }
        return $result;
    }

    // this function SHOULD be abstract but PHP force to implement interface method -
    // another words disallow to make this function abstract
    public function rparse($string, $fromIndex = 0, $restrictedEnd = array())
    {
    }
}