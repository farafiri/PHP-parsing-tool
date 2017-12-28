<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

class BranchStringCondition extends \ParserGenerator\GrammarNode\BranchExtraCondition
{
    private $conditionStrings;

    public function __construct($node, $conditionStrings)
    {
        parent::__construct($node);
        $this->setConditionString($conditionStrings);
    }

    public function setConditionString($conditionStrings)
    {
        $this->conditionStrings = $conditionStrings;
        $this->_functions = [];

        foreach ($conditionStrings as $detailType => $conditionString) {
            $this->_functions[$detailType] = $this->create_function('$string,$fromIndex,$toIndex,$node,$s',
                'return ' . $conditionString . ';');
        }
    }

    public function check($string, $fromIndex, $toIndex, $node)
    {
        $fn = isset($this->_functions[$node->getDetailType()]) ? $this->_functions[$node->getDetailType()] : null;

        if (isset($fn)) {
            /** @var $fn \Closure */
            return $fn($string, $fromIndex, $toIndex, $node, $node->getSubnodes());
        } else {
            return true;
        }
    }

    /**
     * Emulate `create_function` (which was deprecated with PHP 7.2) tailored
     * for the Grammar parser needs.
     *
     * @param string $arguments
     * @param string $body
     * @return \Closure
     */
    protected function create_function($arguments, $body)
    {
        return eval("return function ($arguments) { $body };");
    }
}
