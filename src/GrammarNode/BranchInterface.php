<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

interface BranchInterface extends \ParserGenerator\GrammarNode\NodeInterface, \ParserGenerator\ParserAwareInterface
{
    public function setNode($node);

    public function getNode();

    public function getNodeName();
}