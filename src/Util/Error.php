<?php declare(strict_types=1);

namespace ParserGenerator\Util;

use ParserGenerator\Parser;
use ParserGenerator\GrammarNode\NodeInterface;
use ParserGenerator\GrammarNode\BranchInterface;
use ParserGenerator\GrammarNode\ErrorTrackDecorator;
use ParserGenerator\GrammarNode\Series;
use ParserGenerator\GrammarNode\BaseNode;
use ParserGenerator\GrammarNode\WhitespaceContextCheck;
use ParserGenerator\GrammarNode\WhitespaceNegativeContextCheck;
use ParserGenerator\GrammarNode\Lookahead;
use ParserGenerator\GrammarNode\Choice;
use ParserGenerator\GrammarNode\Regex as RegexNode;
use ParserGenerator\ParsingException;

/**
 * get error data from parser
 * generates proper message for user
 * build ParsingExpected with message and error data
 */
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
     * return improved version of expected
     * improved means: 
     * ordering, 
     * removing positive and negative lookarounds, 
     * spliting ("a"|"b") into "a" or "b",
     * ang generalizing errors.
     * Generalizing error is:
     * in following grammar
     * start :=> "a"
     *       :=> "b".
     * instead of "a" or "b" we get start
     * 
     * @param BaseNode[] $expected
     * @param Parser     $parser
     * @param int        $index
     * @return BaseNode[]
     */
    protected function mapExpected(array $expected, Parser $parser, int $index)
    {
        return $this->order(
                   $this->removeLookaround(
                       $this->degeneralizeChoice(
                           $this->generalizeErrors($expected, $parser))));
    }
    
    /**
     * @param string $str The same input used for parse()
     * @return string
     */
    protected function getErrorString(string $string, int $index, array $expected): string
    {
        $posData     = $this->getLineAndCharacterFromOffset($string, $index);
        $expected    = implode(' or ', array_unique(array_map([$this, 'getNodeName'], $expected)));
        $foundPhrase = $this->getFoundPhrase($string, $index);

        return "line: " . $posData['line'] . ', character: ' . $posData['char'] . "\nexpected: " . $expected . "\n" . $foundPhrase;
    }
    
    protected function getFoundPhrase(string $string, int $index)
    {
        if ($index === strlen($string)) {
            return 'End of string found.';
        };
        
        $found = substr($string, $index);
        
        if (strlen($found) > $this->foundLength) {
            $found = substr($found, 0, $this->foundLength) . '...';
        }
        
        return "found: $found";
    }
    
    protected function getLineAndCharacterFromOffset(string $str, int $offset): array
    {
        $lines = preg_split('/(\r\n|\n\r|\r|\n)/', substr($str, 0, $offset));
        return [
            'line' => count($lines),
            'char' => strlen($lines[count($lines) - 1]) + 1,
        ];
    }
    
    protected function getNodeName(NodeInterface $node): string
    {
        if ($node instanceof RegexNode) {
            $str = Regex::buildStringFromRegex((string) $node);
            if ($str) {
                return '"' . addslashes($str) . '"';
            }
        }
        
        return (string) $node;
    }
    
    protected function degeneralizeChoice(array $expected)
    {
        $result = [];
        
        foreach($expected as $expectedNode) {
            if ($expectedNode instanceof Choice) {
                foreach($expectedNode->getNode() as $option) {
                    $result[] = $option[0];
                }
            } else {
                $result[] = $expectedNode;
            }
        }
        
        return $result;
    }
    
    /**
     * Reorder nodes to show "most important" as first.
     * For example in case: "functionCall($a + $b, $c;"
     * we have unexpected ";" where we would expect: "+" "-" "," "->" ")"
     * note that ")" is probably most informative for reader
     * 
     * @param NodeInterface[] $expected
     * @return NodeInterface[]
     */
    protected function order(array $expected)
    {
        $passes = [['"]"', '")"', '">"', '"}"'],
                   ['","', '"."', '";"', '":"', '"|"'],
                   ['"("']];
        $result = [];
        
        foreach($passes as $pass) {
            foreach($expected as $index => $expectedNode) {
                if (in_array($this->getNodeName($expectedNode), $pass)) {
                    $result[] = $expectedNode;
                    unset($expected[$index]);
                }
            }
        }
        
        return array_merge($result, $expected);
    }
    
    /**
     * @param NodeInterface[] $expected
     * @param bool            $removeLooahead
     * @return NodeInterface[]
     */
    protected function removeLookaround(array $expected, $removeLooahead = false)
    {
        return array_filter($expected, function(NodeInterface $node) use($removeLooahead) {
            return !($node instanceof WhitespaceContextCheck)
                && !($node instanceof WhitespaceNegativeContextCheck)
                && !($node instanceof Lookahead && $removeLooahead);
        });
    }
    
    protected function generalizeErrors(array $errors, Parser $parser)
    {
        $errorsByHash = [];
        foreach ($errors as $error) {
            $errorsByHash[spl_object_hash($error)] = $error;
        }
        
        $states = [];
        $parser->iterateOverNodes(function (NodeInterface $node) use (&$states) {
            if ($node instanceof ErrorTrackDecorator) {
                $states[spl_object_hash($node)] = $node->getMaxCheck();
            }
        });

        $lastParsed = $parser->lastParsed;
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
        $parser->lastParsed = $lastParsed;
        $parser->iterateOverNodes(function (NodeInterface $node) use (&$states) {
            if ($node instanceof ErrorTrackDecorator) {                
                $node->setMaxCheck($states[spl_object_hash($node)]);
            }
        });

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

