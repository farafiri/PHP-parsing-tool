<?php declare(strict_types=1);
//TODO: now this class supports only integers : add real support

namespace ParserGenerator\GrammarNode;

class BooleanNode extends \ParserGenerator\GrammarNode\BaseNode implements \ParserGenerator\GrammarNode\LeafInterface
{
    protected $value;
    
    public function __construct(bool $value)
    {
        $this->value = $value;
    }
    
    public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
    {
        if ($this->value && !isset($restrictedEnd[$fromIndex])) {
            return ['node' => new \ParserGenerator\SyntaxTreeNode\Leaf(""), 'offset' => $fromIndex];
        } else {
            return false;
        }
    }
    
    public function __toString()
    {
        return $this->value ? "true" : "false";
    }
    
    public function getValue()
    {
        return $this->value;
    }
}
