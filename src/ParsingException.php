<?php declare(strict_types=1);

namespace ParserGenerator;

/**
 * This error means string provided to parser don't belong to grammar
 */
class ParsingException extends Exception
{
    protected $index;
    protected $expected;
    protected $parsed;
    
    /**
     * @param string $message
     * @param int $index
     * @param array $expected
     * @param string $parsed
     */
    public function __construct(string $message, int $index, array $expected, string $parsed)
    {
        parent::__construct($message);
        $this->index    = $index;
        $this->expected = $expected;
        $this->parsed   = $parsed;
    }
    
    public function getIndex(): int
    {
        return $this->index;
    }
    
    /**
     * @return ParserGenerator\GrammarNode\BaseNode[]
     */
    public function getExpected()
    {
        return $this->expected;
    }
    
    public function getParsed(): string
    {
        return $this->parsed;
    }
}



