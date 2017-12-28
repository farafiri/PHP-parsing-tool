<?php declare(strict_types=1);

namespace ParserGenerator\Extension\ItemRestrictions;

interface ItemRestrictionInterface
{
    public function check($string, $fromIndex, $toIndex, $node);
}