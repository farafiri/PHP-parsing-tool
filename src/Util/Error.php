<?php declare(strict_types=1);

namespace ParserGenerator\Util;

use ParserGenerator\Parser;
use ParserGenerator\GrammarNode\NodeInterface;
use ParserGenerator\GrammarNode\BranchInterface;
use ParserGenerator\GrammarNode\ErrorTrackDecorator;
use ParserGenerator\GrammarNode\Series;
use ParserGenerator\GrammarNode\BaseNode;
use ParserGenerator\ParsingException;

class Error
{
    protected $foundLength = 20;
    
    public function getError(Parser $parser, $string)
    {
        $errorData = $this->getIndexAndExpected($parser);
        $index     = $errorData['index'];
        $expected  = $this->mapExpected($errorData['expected'], $parser, $index);
        $message   = $this->getErrorString($string, $index, $expected);
        
        return new ParsingException($message, $index, $expected, $string);
    }
    
    /**
     * 
     * @param BaseNode[] $expected
     * @param Parser     $parser
     * @param int        $index
     * @return BaseNode[]
     */
    protected function mapExpected(array $expected, Parser $parser, int $index)
    {
        return $this->generalizeErrors($expected, $parser);
    }
    
    /**
     * @param string $str The same input used for parse()
     * @return string
     */
    protected function getErrorString(string $string, int $index, array $expected): string
    {
        $posData  = $this->getLineAndCharacterFromOffset($string, $index);
        $expected = implode(' or ', $expected);
        $found    = substr($string, $index);
        
        if (strlen($found) > $this->foundLength) {
            $found = substr($found, 0, $this->foundLength) . '...';
        }

        return "line: " . $posData['line'] . ', character: ' . $posData['char'] . "\nexpected: " . $expected . "\nfound: " . $found;
    }
    
    protected function getLineAndCharacterFromOffset(string $str, int $offset): array
    {
        $lines = preg_split('/(\r\n|\n\r|\r|\n)/', substr($str, 0, $offset));
        return [
            'line' => count($lines),
            'char' => strlen($lines[count($lines) - 1]) + 1,
        ];
    }
    
    protected function generalizeErrors(array $errors, Parser $parser)
    {
        $errorsByHash = [];
        foreach ($errors as $error) {
            $errorsByHash[spl_object_hash($error)] = $error;
        }

        foreach ($errorsByHash as $hash => $error) {
            if (isset($errorsByHash[$hash]) && $error instanceof BranchInterface) {
                $parser->parse('', $error);
                $errorData = $this->getIndexAndExpected($parser);
                foreach ($errorData['expected'] as $errorToRemove) {
                    if ($errorToRemove !== $error) {
                        unset($errorsByHash[spl_object_hash($errorToRemove)]);
                    }
                }
            }
        }

        return array_values($errorsByHash);
    }
    
    protected function getIndexAndExpected(Parser $parser): array
    {
        $maxMatch = -1;
        $match = [];
        $parser->iterateOverNodes(function (NodeInterface $node) use (&$maxMatch, &$match) {
            if ($node instanceof ErrorTrackDecorator) {
                if ($maxMatch < $node->getMaxCheck()) {
                    $maxMatch = $node->getMaxCheck();
                    $match = [];
                }

                if ($maxMatch === $node->getMaxCheck()) {
                    $node = $node->getDecoratedNode();

                    if ($node instanceof Series) {
                        $node = $node->getMainNode();
                    }

                    $match[] = $node;
                };
            }
        });

        if ($maxMatch === -1) {
            return [
                'index' => 0,
                'expected' => [$parser->grammar['start']],
            ];
        }

        return [
            'index' => $maxMatch,
            'expected' => $match,
        ];
    }
}

