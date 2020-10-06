<?php

namespace ParserGenerator\GrammarNode;

class OverrideAfterContent extends \ParserGenerator\GrammarNode\BranchDecorator
{
    use WhiteCharsTrait;
    
    public function __construct($node, $eatWhiteChars)
    {
        $this->node = $node;
        $this->setEatWhiteChars($eatWhiteChars);
    }
    
    public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
    {
        $nodeRestrictedEnd = [];
        while (true) {
            $result = $this->node->rparse($string, $fromIndex, $nodeRestrictedEnd);
            if ($result) {
                $node = $result['node'];
                $offset = $result['offset'];
                
                if (isset($nodeRestrictedEnd[$offset])) {
                    return false;
                }
                
                $rightLeaf = $node instanceof \ParserGenerator\SyntaxTreeNode\Branch ? $node->getRightLeaf() : $node;
                $endPos = $offset - strlen($rightLeaf->getAfterContent());
                preg_match($this->whiteCharsRegex, $string, $match, 0, $endPos);
                $endPos += strlen($match[0]);
                if (!isset($restrictedEnd[$endPos])) {
                    return ['node' => static::override($node, $match[0]), 'offset' => $endPos];
                } else {
                    $nodeRestrictedEnd[$offset] = true;
                };
            } else {
                return false;
            }
        }
    }
    
    protected static function override($node, $newAfterContent)
    {
        $node = clone $node;
        if ($node instanceof \ParserGenerator\SyntaxTreeNode\Branch) {
            $property = new \ReflectionProperty(\ParserGenerator\SyntaxTreeNode\Branch::class, 'subnodes');
            $property->setAccessible(true);
            $subnodes = $property->getValue($node);
            $index = count($subnodes) - 1;
            $subnodes[$index] = static::override($subnodes[$index], $newAfterContent);
            $property->setValue($node, $subnodes);
        } elseif ($node instanceof \ParserGenerator\SyntaxTreeNode\Leaf) {
            $property = new \ReflectionProperty(\ParserGenerator\SyntaxTreeNode\Leaf::class, 'afterContent');
            $property->setAccessible(true);
            $property->setValue($node, $newAfterContent);
        } else {
            throw new \Exception("Unknown syntax tree node type");
        }
        
        return $node;
    }
}
