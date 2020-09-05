<?php declare(strict_types=1);

namespace ParserGenerator;

/**
 * class for recursive copying nodes 
 */
class GrammarNodeCopier
{
    /**
     * 
     * @param NodeInterface $node
     * @param callable $callback this function gets node being copied as param and should return following values:
     *                            - false|null if we don't want to copy given node
     *                            - true       if we want to copy given node
     *                            - NodeIterface object to replace provided node                              
     * @return NodeInterface
     */
    public static function copy($node, $callback)
    {
        $_copy = function ($node) use ($callback, &$_copy) {
            static $i;
            if ($node === null) {
                return null;
            }
            if (is_array($node)) {
                $result = [];
                foreach ($node as $index => $subnode) {
                    $result[$index] = $_copy($subnode);
                }
                return $result;
            } else {
                $x = $callback($node);
                if ($x === false || $x === null) {
                    return $node;
                } elseif ($x === true) {
                    return $node->copy($_copy);
                } else {
                    return $x;
                }
            }
        };

        return $node->copy($_copy);
    }
}
