<?php

namespace ParserGenerator\Examples;

/**
 * This is not a real YAML parser
 * This is an example showing how to deal with languages where indentation level is significant (YAML, Python)
 */
class YamlLikeIndentationParser extends \ParserGenerator\Parser
{
    public function __construct()
    {
        parent::__construct($this->getYamlDefinition());
    }

    protected function getYamlDefinition()
    {
        return '
        start            :       => value<""> nl?.
        value<indent>    :string => space* simpleString
                         :object => objValues<indent>.
        objValues<indent>:values => objValue<indent>+
                         :indent => ?(nl indent) objValues<(indent space)>.
        objValue<indent> :value  => nl indent !space simpleString space* ":" value<(indent space)>.
        nl            :=> /^/
                      :=> newLine.
        simpleString  :=> /[a-z0-9_]+/.
        ';
    }

    public function getValue($yamlString)
    {
        $yamlTree = $this->parse($yamlString);

        if (!$yamlTree) {
            return false;
        }

        return $this->getValueOfNode($yamlTree->getSubnode(0));
    }

    protected function getValueOfNode(\ParserGenerator\SyntaxTreeNode\Branch $node)
    {
        if ($node->getType() == 'value' && $node->getDetailType() == 'string') {
            return (string)$node->getSubnode(1);
        } elseif ($node->getType() == 'value' && $node->getDetailType() == 'object') {
            return $this->getValueOfNode($node->getSubnode(0));
        } elseif ($node->getType() == 'objValues' && $node->getDetailType() == 'values') {
            $result = array();
            foreach ($node->getSubnode(0)->getMainNodes() as $objValue) {
                $result[(string)$objValue->getSubnode(2)] = $this->getValueOfNode($objValue->getSubnode(5));
            }
            return $result;
        } elseif ($node->getType() == 'objValues' && $node->getDetailType() == 'indent') {
            return $this->getValueOfNode($node->getSubnode(0));
        }
    }
} 
