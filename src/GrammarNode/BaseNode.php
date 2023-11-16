<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

abstract class BaseNode implements \ParserGenerator\GrammarNode\NodeInterface
{
    public $grammarNode = null;
    protected ?\ParserGenerator\Parser $parser = null;

    abstract public function rparse($string, $fromIndex = 0, $restrictedEnd = []);
}
