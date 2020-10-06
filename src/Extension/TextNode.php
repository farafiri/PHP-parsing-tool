<?php declare(strict_types=1);

namespace ParserGenerator\Extension;

class TextNode extends \ParserGenerator\Extension\SequenceItem
{
    protected function getGrammarGrammarSequence()
    {
        return [':string'];
    }

    protected function _buildSequenceItem(&$grammar, $sequenceItem, $grammarParser, $options)
    {
        return static::getGrammarNode((string)$sequenceItem->getSubnode(0)->getValue(), $options['ignoreWhitespaces'], $options['caseInsensitive']);
    }
    
    /**
     * 
     * @param string         $str
     * @param string|boolean $ignoreWhitespaces
     * @param boolean        $caseInsensitive
     * @return \ParserGenerator\GrammarNode\NodeInterface one of \ParserGenerator\GrammarNode\Regex|\ParserGenerator\GrammarNode\TextS|\ParserGenerator\GrammarNode\Text
     */
    public static function getGrammarNode($str, $ignoreWhitespaces, $caseInsensitive)
    {
        if ($caseInsensitive) {
            $regex = \ParserGenerator\Util\Regex::buildRegexFromString($str);
            return new \ParserGenerator\GrammarNode\Regex($regex, $ignoreWhitespaces, $caseInsensitive, $str);
        }

        if ($ignoreWhitespaces) {
            return new \ParserGenerator\GrammarNode\TextS($str, $ignoreWhitespaces);
        }

        return new \ParserGenerator\GrammarNode\Text($str);
    }
    
    /**
     * @param \ParserGenerator\GrammarNode\NodeInterface $node
     * @return string|null
     */
    public static function getText(\ParserGenerator\GrammarNode\NodeInterface $node)
    {
        if ($node instanceof \ParserGenerator\GrammarNode\Text) {
            return $node->getString();
        } elseif ($node instanceof \ParserGenerator\GrammarNode\Regex) {
            return $node->getText();
        } else {
            return null;
        }
    }
}
