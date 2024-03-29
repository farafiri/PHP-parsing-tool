<?php declare(strict_types=1);

namespace ParserGenerator\GrammarNode;

class Unorder extends \ParserGenerator\GrammarNode\BaseNode implements \ParserGenerator\ParserAwareInterface
{
    const MAX = 1000000;

    protected                         $separator;
    protected                         $resultType;
    protected                         $tmpNodeName;
    protected                         $choices = [];
    protected                         $mod = [];

    public function __construct($separator, $resultType = 'unorder')
    {
        $this->separator = $separator;
        $this->resultType = $resultType;
        $this->setTmpNodeName();
    }

    protected function setTmpNodeName()
    {
        $this->tmpNodeName = '&unorder/' . spl_object_hash($this);
    }

    public function addChoice($choice, $mod)
    {
        $this->choices[] = $choice;
        $this->mod[] = $mod;
    }

    protected function internalParse($string, $fromIndex, $restrictedEnd, $required, $left)
    {
        foreach ($this->choices as $key => $choice) {
            if ($left[$key] > 0) {
                $choiceRestrictedEnd = [];
                $isRequired = !empty($required[$key]);
                unset($required[$key]);
                $left[$key]--;
                while ($choiceResult = $choice->rparse($string, $fromIndex, $choiceRestrictedEnd)) {
                    $afterChoiceIndex = $choiceResult['offset'];
                    $separatorRestrictedEnd = [];
                    while ($separatorResult = $this->separator->rparse($string, $afterChoiceIndex,
                        $separatorRestrictedEnd)) {
                        $afterSeparatorIndex = $separatorResult['offset'];
                        if ($next = $this->internalParse($string, $afterSeparatorIndex, $restrictedEnd, $required,
                            $left)) {
                            array_push($next['nodes'], $separatorResult['node'], $choiceResult['node']);
                            return $next;
                        }

                        $separatorRestrictedEnd[$afterSeparatorIndex] = $afterSeparatorIndex;
                    }


                    $choiceRestrictedEnd[$afterChoiceIndex] = $afterChoiceIndex;
                }

                if (empty($required)) {
                    $choiceResult = $choice->rparse($string, $fromIndex, $restrictedEnd);
                    if ($choiceResult) {
                        return ['nodes' => [$choiceResult['node']], 'offset' => $choiceResult['offset']];
                    }
                }

                $left[$key]++;
                if ($isRequired) {
                    $required[$key] = true;
                }
            }
        }

        return false;
    }

    public function rparse($string, $fromIndex = 0, $restrictedEnd = [])
    {
        $required = [];
        foreach ($this->choices as $key => $choice) {
            $mod = $this->mod[$key];
            $left[$key] = ($mod == '*' || $mod == '+') ? static::MAX : 1;
            if ($mod == '' || $mod == '1' || $mod == '+') {
                $required[$key] = 1;
            }
        }

        if ($result = $this->internalParse($string, $fromIndex, $restrictedEnd, $required, $left)) {
            $node = new \ParserGenerator\SyntaxTreeNode\Series($this->resultType, '', array_reverse($result['nodes']),
                true);
            return ['node' => $node, 'offset' => $result['offset']];
        }

        return false;
    }

    public function getTmpNodeName()
    {
        return $this->tmpNodeName;
    }

    public function setParser(\ParserGenerator\Parser $parser)
    {
        $this->parser = $parser;
        foreach ($this->choices as $choice) {
            if ($choice instanceof \ParserGenerator\ParserAwareInterface) {
                $choice->setParser($parser);
            }
        }
    }

    public function getParser()
    {
        return $this->parser;
    }

    public function __toString()
    {
        return "unorder";
        return '(' . implode(' | ', $this->choices) . ')';
    }

    public function copy($copyCallback)
    {
        $result = clone $this;
        $result->separator = $copyCallback($this->separator);
        $result->choices = $copyCallback($this->choices);
        $result->setTmpNodeName();
        return $result;
    }
}
