<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 17.02.17
 * Time: 22:29
 */

namespace ParserGenerator;


class GrammarNodeCopier
{
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
