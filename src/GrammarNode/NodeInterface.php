<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

interface NodeInterface
{
    public function rparse($string, $fromIndex, $restrictedEnd);
}