<?php declare(strict_types=1);

namespace ParserGenerator\Examples;

use ParserGenerator\Exception;

class JSONParser extends \ParserGenerator\Parser
{
    public function __construct()
    {
        parent::__construct($this->getJSONDefinition(), ['ignoreWhitespaces' => true]);
    }

    protected function getJSONDefinition()
    {
        return '
        start:       => value.
        value:bool   => ("true"|"false")
             :string => string
             :number => -inf..inf
             :array  => "[" value*"," "]"
             :object => "{" objValue*"," "}".
        objValue:    => string ":" value.
        ';
    }

    public function getValue($jsonString)
    {
        $jsonTree = $this->parse($jsonString);

        if (!$jsonTree) {
            throw new Exception("Given string is not proper JSON");
        }

        return $this->getValueOfNode($jsonTree->getSubnode(0));
    }

    protected function getValueOfNode(\ParserGenerator\SyntaxTreeNode\Branch $node)
    {
        switch ($node->getDetailType()) {
            case "bool":
                return (string)$node === "true";
            case "string":
            case "number":
                return $node->getSubnode(0)->getValue();
            case "array":
                $result = [];

                foreach ($node->getSubnode(1)->getMainNodes() as $valueNode) {
                    $result[] = $this->getValueOfNode($valueNode);
                }

                return $result;
            case "object":
                $result = [];

                foreach ($node->getSubnode(1)->getMainNodes() as $objValueNode) {
                    $result[$objValueNode->getSubnode(0)->getValue()] = $this->getValueOfNode($objValueNode->getSubnode(2));
                }

                return $result;
        }
    }
}
