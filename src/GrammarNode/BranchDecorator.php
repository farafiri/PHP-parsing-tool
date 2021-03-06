<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

class BranchDecorator extends \ParserGenerator\GrammarNode\Decorator implements \ParserGenerator\GrammarNode\BranchInterface
{
    public function setParser(\ParserGenerator\Parser $parser)
    {
        return $this->node->setParser($parser);
    }

    public function getParser()
    {
        return $this->node->getParser();
    }

    public function setNode($node)
    {
        return $this->node->setNode($node);
    }

    public function getNode()
    {
        return $this->node->getNode();
    }

    public function getNodeName()
    {
        return $this->node->getNodeName();
    }

    public function __toString()
    {
        return $this->getNodeName();
    }
}