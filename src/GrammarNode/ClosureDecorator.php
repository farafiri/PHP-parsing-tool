<?php

namespace ParserGenerator\GrammarNode;

class ClosureDecorator extends BaseNode implements LeafInterface
{
    protected $callback;
    protected $name;
    
    public function __construct(callable $callback, $name)
    {
        $this->callback = $callback;
        $this->name = $name;
    }
    
   public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
   {
       $callback = $this->callback;
       $parsed = $callback($string, $fromIndex, $restrictedEnd);
       if (!$parsed) {
           return false;
       }
       
       if ($parsed === true) {
           $parsed = ['node' => new \ParserGenerator\SyntaxTreeNode\Leaf(''), 'offset' => $fromIndex];
       }
       
       // if offset set in restrictedEnd => $callback ignores $restrictedEnd parameter and always return same value && returned value is restricted => no match
       if (isset($restrictedEnd[$parsed['offset']])) {
           return false;
       }
       
       return $parsed;
   }
   
   public function __toString()
   {
       return $this->name;
   }
}
