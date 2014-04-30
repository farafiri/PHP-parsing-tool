<?php

namespace ParserGenerator\GrammarNode;

interface NodeInterface
{
    public function canBeEmpty();

    public function startChars();

    public function rparse($string, $fromIndex, $restrictedEnd);
}