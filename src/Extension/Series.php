<?php declare(strict_types=1);

namespace ParserGenerator\Extension;

use ParserGenerator\GrammarNode\BranchFactory;

class Series extends \ParserGenerator\Extension\SequenceItem
{
    protected function getGrammarGrammarSequence()
    {
        $operator = ':/(\*{1,2}|\+{1,2}|\?{1,2})/';
        $noWhiteChar = new \ParserGenerator\GrammarNode\WhitespaceNegativeContextCheck(null);

        return [
            [':sequenceItem', $noWhiteChar, $operator, $noWhiteChar, ':sequenceItem'],
            [':sequenceItem', $noWhiteChar, $operator],
        ];
    }

    protected function _buildSequenceItem(&$grammar, $sequenceItem, $grammarParser, $options)
    {
        $main = $grammarParser->buildSequenceItem($grammar, $sequenceItem->getSubnode(0), $options);
        if ($options['trackError']) {
            $main = new \ParserGenerator\GrammarNode\ErrorTrackDecorator($main);
        }
        
        if ($sequenceItem->getSubnode(4)) {
            $separator = $grammarParser->buildSequenceItem($grammar, $sequenceItem->getSubnode(4), $options);
            if ($options['trackError']) {
                $separator = new \ParserGenerator\GrammarNode\ErrorTrackDecorator($separator);
            }
        } else {
            $separator = null;
        }
        
        $forceGreedy = $options['defaultBranchType'] === BranchFactory::PEG;
        $operator = (string)$sequenceItem->getSubnode(2);
        switch ($operator) {
            case '++':
            case '**':
            case '+':
            case '*':
                $greedy = in_array($operator, ['**', '++']) || $forceGreedy;
                $node = new \ParserGenerator\GrammarNode\Series($main, $separator,
                    in_array($operator, ['*', '**']), $greedy, $options['defaultBranchType']);
                $node->setParser($options['parser']);

                return $node;
            case '??':
            case '?':
                $toStringCallback = function($_, $choices) use ($operator) { return $choices[$operator == '??' ? 0 : 1] . $operator; };
                
                $empty = new \ParserGenerator\GrammarNode\Text('');
                $choices = ($operator == '??' || $forceGreedy) ? [$main, $empty] : [$empty, $main];
                $node = new \ParserGenerator\GrammarNode\Choice($choices, $toStringCallback);

                $node->setParser($options['parser']);

                return $node;
        }
    }
}
