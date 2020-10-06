<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

use ParserGenerator\Exception;
use ParserGenerator\GrammarNodeCopier;

class ParametrizedNode extends BaseNode implements \ParserGenerator\ParserAwareInterface
{
    protected $abstractNode;
    protected $params;
    protected $subnode;
    protected $parser;

    public function __construct($abstractNode, $params)
    {
        $this->abstractNode = $abstractNode;
        $this->params = $params;
    }

    public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
    {
        if (!$this->subnode) {
            $this->subnode = $this->createNode();
        }

        return $this->subnode->rparse($string, $fromIndex, $restrictedEnd);
    }

    protected function createNode()
    {
        $params = $this->params;
        $parser = $this->parser;
        
        if ($this->abstractNode instanceof \ParserGenerator\NodeFactory) {
            return $this->abstractNode->getNode($params, $parser);    
        }
        
        return GrammarNodeCopier::copy($this->abstractNode, function ($node) use ($params, $parser) {
            if ($node instanceof ErrorTrackDecorator) {
                $node = $node->getDecoratedNode();
            }

            if ($node instanceof ParameterNode) {
                if (empty($params[$node->getIndex()])) {
                    throw new Exception("Parameter " . $node->getParameterName() . " with index " . $node->getIndex() . " in branch " . $node->getBranchName() . " not provided");
                }
                return $params[$node->getIndex()];
            }

            if ($node instanceof LeafInterface) {
                return false;
            }

            if ($node instanceof BranchInterface) {
                $name = $node->getNodeName();
                if (isset($parser->grammar[$name]) && $parser->grammar[$name] === $node) {
                    return false;
                }
            }

            return true;
        });
    }

    public function __toString()
    {
        return $this->abstractNode . '<' . implode(',', $this->params) . '>';
    }

    public function setParser(\ParserGenerator\Parser $parser)
    {
        $this->parser = $parser;
    }

    public function getParser()
    {
        return $this->parser;
    }
    
    public function getNode()
    {
        if (!$this->subnode) {
            $this->subnode = $this->createNode();
        }
        
        $subnode = Decorator::undecorate($this->subnode);
        if ($subnode instanceof Branch || $subnode instanceof Choice) {
            return $subnode->getNode();
        } else {
            return [[$this->subnode]];
        }
    }

    public function copy($callback, $collectCallback, $copyAbstractNode)
    {
        $copy = new static($this->abstractNode, $this->params);
        $collectCallback($this, $copy);
        if ($copyAbstractNode) {
            $copy->abstractNode = $callback($this->abstractNode);
        }
        $copy->params       = $callback($this->params);
        $copy->setParser($this->getParser());
        return $copy;
    }
}
