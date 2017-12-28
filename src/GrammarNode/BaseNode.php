<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

abstract class BaseNode implements \ParserGenerator\GrammarNode\NodeInterface
{
    abstract public function rparse($string, $fromIndex = 0, $restrictedEnd = array());
}
