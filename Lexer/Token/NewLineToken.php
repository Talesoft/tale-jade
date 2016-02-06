<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class NewLineToken extends TokenBase
{

    public function __toString()
    {

        return parent::__toString()."\n";
    }
}