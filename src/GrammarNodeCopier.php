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
    public static function copy($node, $callback, $copyAbstractNode = false)
    {
        $createdNodes = [];
        $collectCallback = function ($origin, $copy) use (&$createdNodes) {
            $createdNodes[spl_object_hash($origin)] = $copy;
        };
        
        $_copy = function ($node) use ($callback, &$_copy, &$createdNodes, $collectCallback, $copyAbstractNode) {
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
            } elseif (isset($createdNodes[spl_object_hash($node)])) {
                return $createdNodes[spl_object_hash($node)];
            } else {
                $x = $callback($node, $_copy);
                if ($x === false || $x === null) {
                    $result = $node;
                } elseif ($x === true) {
                    if ($node instanceof NodeFactory) {
                        return new class($node, $_copy) extends \ParserGenerator\NodeFactory {
                            protected $nodeFactory;
                            protected $copy;

                            public function __construct($nodeFactory, $copy)
                            {
                                $this->nodeFactory = $nodeFactory;  
                                $this->copy        = $copy;
                            }

                            function getNode($params, \ParserGenerator\Parser $parser): GrammarNode\NodeInterface 
                            {
                                $copy = $this->copy;
                                return $copy($this->nodeFactory->getNode($params, $parser));
                            }
                        };
                    } else {
                        $result = $node->copy($_copy, $collectCallback, $copyAbstractNode);
                    }
                } else {
                    $result = $x;
                }
                
                $createdNodes[spl_object_hash($node)] = $result;
                
                return $result;
            }
        };

        return $node->copy($_copy, $collectCallback, $copyAbstractNode);
    }
}
