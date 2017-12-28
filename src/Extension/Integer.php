<?php declare(strict_types=1);

namespace ParserGenerator\Extension;

class Integer extends \ParserGenerator\Extension\SequenceItem
{
    const _NAMESPACE = 'IntegerPlugin';

    public function extendGrammar($grammarGrammar)
    {
        $grammarGrammar[$this->getNS(null, false)] = [
            [
                $this->getNS('LowBound'),
                new \ParserGenerator\GrammarNode\Text('..'),
                $this->getNS('HiBound'),
                $this->getNs('modifiers'),
                '',
            ],
        ];

        $grammarGrammar[$this->getNS('LowBound', false)] = [
            [new \ParserGenerator\GrammarNode\Text('-inf')],
            [new \ParserGenerator\GrammarNode\Text('-infinity')],
            'int' => [
                new \ParserGenerator\GrammarNode\Numeric([
                    'formatHex' => true,
                    'formatBin' => true,
                    'allowFixedCharacters' => true,
                ]),
            ],
        ];
        $grammarGrammar[$this->getNS('HiBound', false)] = [
            [new \ParserGenerator\GrammarNode\Text('inf')],
            [new \ParserGenerator\GrammarNode\Text('infinity')],
            'int' => [
                new \ParserGenerator\GrammarNode\Numeric([
                    'formatHex' => true,
                    'formatBin' => true,
                    'allowFixedCharacters' => true,
                ]),
            ],
        ];

        $grammarGrammar[$this->getNS('modifiers', false)] = [
            [new \ParserGenerator\GrammarNode\Text('/'), $this->getNS('modifierList')],
            [''],
        ];

        $grammarGrammar[$this->getNS('modifierList', false)] = [
            [$this->getNS('modifier'), $this->getNS('modifierList')],
            [$this->getNS('modifier')],
        ];

        $grammarGrammar[$this->getNS('modifier', false)] = [
            'formatHex' => [new \ParserGenerator\GrammarNode\Text('h')],
            'formatDec' => [new \ParserGenerator\GrammarNode\Text('d')],
            'formatOct' => [new \ParserGenerator\GrammarNode\Text('o')],
            'formatBin' => [new \ParserGenerator\GrammarNode\Text('b')],
            'fixed' => [new \ParserGenerator\GrammarNode\Regex('/\d+/')],
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
        $numericOptions = [];
        $numericOptions['eatWhiteChars'] = !empty($options['ignoreWhitespaces']);

        $item = $sequenceItem->getSubnode(0);
        $min = $item->getSubnode(0);
        $max = $item->getSubnode(2);
        $modifiers = $item->findAll($this->getNS('modifier', false));

        if ($min->getDetailType() === 'int') {
            $numericOptions['min'] = $min->getSubnode(0)->getValue();

            if (!count($modifiers)) {
                if ($min->getSubnode(0)->getFixedCharacters()) {
                    $numericOptions['requireFixedCharacters'] = $min->getSubnode(0)->getFixedCharacters();
                }

                if ($min->getSubnode(0)->getBase() === 16) {
                    $numericOptions['formatHex'] = true;
                }

                if ($min->getSubnode(0)->getBase() === 2) {
                    $numericOptions['formatBin'] = true;
                }
            }
        }

        if ($max->getDetailType() === 'int') {
            $numericOptions['max'] = $max->getSubnode(0)->getValue();

            if (!count($modifiers)) {
                if ($max->getSubnode(0)->getFixedCharacters()) {
                    $numericOptions['requireFixedCharacters'] = $max->getSubnode(0)->getFixedCharacters();
                }

                if ($max->getSubnode(0)->getBase() === 16) {
                    $numericOptions['formatHex'] = true;
                }

                if ($max->getSubnode(0)->getBase() === 2) {
                    $numericOptions['formatBin'] = true;
                }
            }
        }

        if (count($modifiers)) {
            $numericOptions['formatDec'] = false;
        }

        foreach ($modifiers as $modifier) {
            if (in_array($modifier->getDetailType(), ['formatDec', 'formatHex', 'formatOct', 'formatBin'])) {
                $numericOptions[$modifier->getDetailType()] = true;
            } elseif ($modifier->getDetailType() === 'fixed') {
                if ((string)$modifier === '0') {
                    $numericOptions['allowFixedCharacters'] = true;
                } else {
                    $numericOptions['requireFixedCharacters'] = (int)(string)$modifier;
                }
            }
        }

        return new \ParserGenerator\GrammarNode\Numeric($numericOptions);
    }
}
