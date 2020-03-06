<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

class BacktraceNode extends Decorator
{
    protected $tracer;
    
    public function __construct($node, $tracer)
    {
        $this->node = $node;
        $this->tracer = $tracer;
    }
    
    public function rparse($string, $fromIndex, $restrictedEnd)
    {
        if ((int) $fromIndex === $this->tracer->index) {
            $this->tracer->addBacktrace(debug_backtrace());
        };
        
        return $this->node->rparse($string, $fromIndex, $restrictedEnd);
    }
}


