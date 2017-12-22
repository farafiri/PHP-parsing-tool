<?php

namespace ParserGenerator;


interface ParserAwareInterface
{
    public function getParser();

    public function setParser(Parser $parser);
}
