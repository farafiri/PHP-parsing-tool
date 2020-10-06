<?php declare(strict_types=1);

namespace ParserGenerator\Extension;

class Text extends \ParserGenerator\Extension\SequenceItem
{
    const _NAMESPACE = 'TextPlugin';

    public function extendGrammar($grammarGrammar)
    {
        $grammarGrammar[$this->getNS(null, false)] = [
            [
                ':/text(?![a-zA-Z0-9_])/',
            ],
        ];

        return parent::extendGrammar($grammarGrammar);
    }

    protected function getNS($node = '', $addColon = true)
    {
        return ($addColon ? ':' : '') . static::_NAMESPACE . ($node ? '_' . $node : '');
    }

    protected function getGrammarGrammarSequence()
    {
        return [$this->getNS('')];
    }

    protected function _buildSequenceItem(&$grammar, $sequenceItem, $grammarParser, $options)
    {
        return new \ParserGenerator\GrammarNode\AnyText($options);
    }
}
