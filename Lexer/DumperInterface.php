<?php

namespace Tale\Jade\Lexer;

interface DumperInterface
{

    public function dump(\Generator $tokens);
}