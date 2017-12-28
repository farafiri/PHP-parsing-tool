<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

use ParserGenerator\Exception;

class ParameterNode extends BaseNode
{
    protected $index;
    protected $branchName;
    protected $parameterName;

    public function __construct($index, $branchName, $parameterName)
    {
        $this->index = $index;
        $this->branchName = $branchName;
        $this->parameterName = $parameterName;
    }

    public function rparse($string, $fromIndex = 0, $restrictedEnd = array())
    {
        throw new Exception("this function should be never called on this node type");
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function getBranchName()
    {
        return $this->branchName;
    }

    public function getParameterName()
    {
        return $this->parameterName;
    }
}
