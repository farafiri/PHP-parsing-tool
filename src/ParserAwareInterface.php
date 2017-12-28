<?php declare(strict_types=1);

namespace ParserGenerator;


interface ParserAwareInterface
{
    public function getParser();

    public function setParser(Parser $parser);
}
