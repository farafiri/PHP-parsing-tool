<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

class Lookahead extends \ParserGenerator\GrammarNode\BaseNode
{
    protected $lookaheadNode;
    protected $mainNode;
    protected $before;
    protected $positive;

    public function __construct($lookaheadNode, $mainNode = null, $before = true, $positive = true)
    {
        $this->lookaheadNode = $lookaheadNode;
        $this->mainNode = $mainNode;
        $this->before = $before;
        $this->positive = $positive;
    }

    public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
    {
        if ($this->mainNode === null) {
            if (isset($restrictedEnd[$fromIndex])) {
                return false;
            }

            $match = $this->lookaheadNode->rparse($string, $fromIndex, []) !== false;

            if ($match === $this->positive) {
                return ['node' => new \ParserGenerator\SyntaxTreeNode\Leaf(''), 'offset' => $fromIndex];
            } else {
                return false;
            }
        } elseif ($this->before) {
            $match = $this->lookaheadNode->rparse($string, $fromIndex, []) !== false;

            if ($match !== $this->positive) {
                return false;
            }

            return $this->mainNode->rparse($string, $fromIndex, $restrictedEnd);
        } else { // !$this->before
            while ($rparseResult = $this->mainNode->rparse($string, $fromIndex, $restrictedEnd)) {
                $offset = $rparseResult['offset'];
                $match = $this->lookaheadNode->rparse($string, $offset, []) !== false;

                if ($match === $this->positive) {
                    return $rparseResult;
                } else {
                    $restrictedEnd[$offset] = $offset;
                }
            }

            return false;
        }
    }

    public function getUsedNodes($startWithOnly = false, $onlyPositive = false)
    {
        $result = [];
        if ((!$startWithOnly || $this->before) && (!$onlyPositive || $this->positive)) {
            $result[] = $this->lookaheadNode;
        }
        if ($this->mainNode !== null) {
            $result[] = $this->mainNode;
        }

        return $result;
    }

    public function __toString()
    {
        $lookaheadStr = ($this->positive ? '?' : '!') . $this->lookaheadNode;
        if ($this->mainNode === null) {
            return $lookaheadStr;
        } elseif ($this->before) {
            return $lookaheadStr . ' ' . $this->mainNode;
        } else {
            return $this->mainNode . ' ' . $lookaheadStr;
        }
    }

    public function copy($copyCallback)
    {
        $copy = clone $this;
        $copy->lookaheadNode = $copyCallback($this->lookaheadNode);
        $copy->mainNode = $copyCallback($this->mainNode);
        return $copy;
    }
    
    public function isPositive()
    {
        return $this->positive;
    }
    
    public function getLookaheadNode()
    {
        return $this->lookaheadNode;
    }
}
