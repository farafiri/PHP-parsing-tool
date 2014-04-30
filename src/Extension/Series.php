<?php

namespace ParserGenerator\Extension;

class Series extends \ParserGenerator\Extension\SequenceItem
{
    protected function getGrammarGrammarSequence()
    {
        $operator = ':/(\*{1,2}|\+{1,2}|\?{1,2})/';
        $noWhiteChar = new \ParserGenerator\GrammarNode\WhitespaceNegativeContextCheck(null);

        return array(
            array(':sequenceItem', $noWhiteChar, $operator, $noWhiteChar, ':sequenceItem'),
            array(':sequenceItem', $noWhiteChar, $operator)
        );
    }

    protected function _buildSequenceItem(&$grammar, $sequenceItem, $grammarParser, $options)
    {
        $main = $grammarParser->buildSequenceItem($grammar, $sequenceItem->getSubnode(0), $options);
        if ($sequenceItem->getSubnode(4)) {
            $separator = $grammarParser->buildSequenceItem($grammar, $sequenceItem->getSubnode(4), $options);
            if (!empty($options['trackError'])) {
                $separator = new \ParserGenerator\GrammarNode\ErrorTrackDecorator($separator);
            }
        } else {
            $separator = null;
        }

        $operator = (string)$sequenceItem->getSubnode(2);
        switch ($operator) {
            case '++':
            case '**':
            case '+':
            case '*':
                $node = new \ParserGenerator\GrammarNode\Series($main, $separator, in_array($operator, array('*', '**')), in_array($operator, array('**', '++')));
                if (isset($options['parser'])) {
                    $node->setParser($options['parser']);
                }

                return $node;
			case '??':
			case '?':
			    $empty = new \ParserGenerator\GrammarNode\Text('');
				$choices = $operator == '??' ? array($main, $empty) : array($empty, $main);
			    $node = new \ParserGenerator\GrammarNode\Choice($choices);
				
				if (isset($options['parser'])) {
                    $node->setParser($options['parser']);
                }

                return $node;
        }
    }
}

\ParserGenerator\GrammarParser::$defaultPlugins[] = new Series();