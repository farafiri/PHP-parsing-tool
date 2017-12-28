<?php declare(strict_types=1);

namespace ParserGenerator\Extension;

use ParserGenerator\GrammarNode\ParameterNode;
use ParserGenerator\GrammarNode\ParametrizedNode as GrammarNode;

class ParametrizedNode extends Base
{
    /** @var array $nodeParams $nodeParams[$nodeName][$parameterName] => $parameterIndex */
    protected $nodeParams;

    public function extendGrammar($grammarGrammar)
    {
        $this->nodeParams = [];

        $grammarGrammar['grammarBranch']['standard'] = $this->insert($grammarGrammar['grammarBranch']['standard'],
            ':branchName', ':branchParamsDef');

        $grammarGrammar['branchParamsDef'] = [
            ['<', ':branchParamsDefList', '>'],
            [''],
        ];

        $grammarGrammar['branchParamsDefList'] = [
            'last' => [':branchName'],
            'notLast' => [':branchName', ',', ':branchParamsDefList'],
        ];

        $grammarGrammar['sequenceItem']['parametrizedNode'] = [
            ':branchName',
            '<',
            ':parametrizedNodeParamsList',
            '>',
        ];
        $grammarGrammar['parametrizedNodeParamsList'] = [
            'last' => [':sequenceItem'],
            'notLast' => [':sequenceItem', ',', ':parametrizedNodeParamsList'],
        ];

        return $grammarGrammar;
    }

    public function modifyBranches($grammar, $parsedGrammar, $grammarParser, $options)
    {
        foreach ($parsedGrammar->findAll('grammarBranch:standard') as $grammarBranch) {
            $name = (string)$grammarBranch->findFirst('branchName');
            $i = 0;
            foreach ($grammarBranch->findFirst('branchParamsDef')->findAll('branchName') as $branchName) {
                $this->nodeParams[$name][(string)$branchName] = new ParameterNode($i++, $name, (string)$branchName);
            }
        }

        return $grammar;
    }

    function buildSequenceItem(&$grammar, $sequenceItem, $grammarParser, $options)
    {
        if ($sequenceItem->getDetailType() === 'branch') {
            $branchNode = $sequenceItem->nearestOwner('grammarBranch:standard');
            $branchName = $branchNode ? (string)$branchNode->findFirst('branchName') : null;
            if ($branchNode && isset($this->nodeParams[$branchName][(string)$sequenceItem])) {
                return $this->nodeParams[$branchName][(string)$sequenceItem];
            }
            return null;
        }

        if ($sequenceItem->getDetailType() === 'parametrizedNode') {
            $params = [];
            foreach ($sequenceItem->findFirst('parametrizedNodeParamsList')->findAll('sequenceItem') as $param) {
                $params[] = $grammarParser->buildSequenceItem($grammar, $param, $options);
            }

            $node = new GrammarNode($grammar[(string)$sequenceItem->findFirst('branchName')], $params);
            $node->setParser($options['parser']);
            return $node;
        }
    }
}
