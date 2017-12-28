<?php declare(strict_types=1);

namespace ParserGenerator\Extension;

class RuleCondition extends \ParserGenerator\Extension\Base
{
    public function extendGrammar($grammarGrammar)
    {
        $grammarGrammar['rule']['standard'][] = ':possibleRuleCondition';
        $grammarGrammar['possibleRuleCondition'] = [[':ruleCondition'], ['']];
        $grammarGrammar['ruleCondition']['standard'] = ['<?', ':/([^?]|\?[^>])+/', '?>'];

        return $grammarGrammar;
    }

    function modifyBranches($grammar, $parsedGrammar, $grammarParser, $options)
    {
        foreach ($parsedGrammar->findAll('grammarBranch') as $grammarBranch) {
            $functions = [];
            foreach ($grammarBranch->findAll('rule') as $ruleIndex => $rule) {
                $ruleName = (string)$rule->findFirst('ruleName') ?: $ruleIndex;
                if ($condition = $rule->findFirst('ruleCondition')) {
                    $functions[$ruleName] = (string)$condition->getSubnode(1);
                }
            }

            if (count($functions)) {
                $branchName = (string)$grammarBranch->findFirst('branchName');
                $grammar[$branchName] = new \ParserGenerator\GrammarNode\BranchStringCondition($grammar[$branchName],
                    $functions);
            }
        }

        return $grammar;
    }
}
