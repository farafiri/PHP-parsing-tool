<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

class Series extends \ParserGenerator\GrammarNode\BranchDecorator
{
    protected $resultType = 'list';
    protected $resultDetailType = '';
    protected $tmpNodeName;
    protected $mainNode;
    protected $separator;
    protected $from0;
    protected $greedy;
    protected $type;

    public function __construct($mainNode, $separator, $from0, $greedy, $type)
    {
        $this->mainNode = $mainNode;
        $this->separator = $separator;
        $this->from0 = $from0;
        $this->greedy = $greedy;
        $this->tmpNodeName = '&series/' . spl_object_hash($this);
        $this->type = $type;
        
        $undecorated = \ParserGenerator\GrammarNode\Decorator::undecorate($mainNode); //$mainNode instanceof \ParserGenerator\GrammarNode\ErrorTrackDecorator ? $mainNode->getDecoratedNode() : $mainNode;

        if ($undecorated instanceof \ParserGenerator\GrammarNode\BranchInterface) {
            $this->resultDetailType = $undecorated->getNodeName();
        } elseif ($undecorated instanceof \ParserGenerator\GrammarNode\Text) {
            $this->resultDetailType = $undecorated->getString();
        } elseif ($undecorated instanceof \ParserGenerator\GrammarNode\Regex) {
            $this->resultDetailType = $undecorated->getRegex();
        } elseif ($undecorated instanceof \ParserGenerator\GrammarNode\AnyText) {
            $this->resultDetailType = 'text';
        } else {
            $this->resultDetailType = '';
        }

        $this->node = BranchFactory::createBranch($this->type, $this->tmpNodeName);

        $ruleGo = $separator ? [$mainNode, $separator, $this->node] : [$mainNode, $this->node];
        $ruleStop = [$mainNode];

        if ($greedy) {
            $node = ['go' => $ruleGo, 'stop' => $ruleStop];
        } else {
            $node = ['stop' => $ruleStop, 'go' => $ruleGo];
        }

        $this->node->setNode($node);
    }

    public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
    {
        if ($this->from0 && !$this->greedy && !isset($restrictedEnd[$fromIndex])) {
            return [
                'node' => new \ParserGenerator\SyntaxTreeNode\Series($this->resultType, $this->resultDetailType,
                    [], (bool)$this->separator),
                'offset' => $fromIndex,
            ];
        }

        if ($rparseResult = $this->node->rparse($string, $fromIndex, $restrictedEnd)) {
            $rparseResult['node'] = $this->getFlattenNode($rparseResult['node']);
            return $rparseResult;
        }

        if ($this->from0 && !isset($restrictedEnd[$fromIndex])) {
            return [
                'node' => new \ParserGenerator\SyntaxTreeNode\Series($this->resultType, $this->resultDetailType,
                    [], (bool)$this->separator),
                'offset' => $fromIndex,
            ];
        }

        return false;
    }

    protected function getFlattenNode($ast)
    {
        $astSubnodes = [];
        while ($ast->getDetailType() == 'go') {
            $astSubnodes[] = $ast->getSubnode(0);
            if ($this->separator) {
                $astSubnodes[] = $ast->getSubnode(1);
                $ast = $ast->getSubnode(2);
            } else {
                $ast = $ast->getSubnode(1);
            }
        }
        $astSubnodes[] = $ast->getSubnode(0);

        return new \ParserGenerator\SyntaxTreeNode\Series($this->resultType, $this->resultDetailType, $astSubnodes,
            (bool)$this->separator);
    }

    public function getNode()
    {
        $node = $this->separator ? [[$this->mainNode, $this->separator]] : [[$this->mainNode]];
        if ($this->from0) {
            $node[] = [];
        }
        return $node;
    }

    public function getMainNode()
    {
        return $this->mainNode;
    }
    
    public function getSeparator()
    {
        return $this->separator;
    }
    
    public function getFrom0()
    {
        return $this->from0;
    }

    public function __toString()
    {
        $op = [['+', '++'], ['*', '**']];
        return $this->mainNode . $op[$this->from0][$this->greedy] . ($this->separator ?: '');
    }

    public function copy($copyCallback)
    {
        $copy = new static($copyCallback($this->mainNode), $copyCallback($this->separator), $this->from0,
            $this->greedy, $this->type);
        $copy->setParser($this->getParser());
        return $copy;
    }
}
