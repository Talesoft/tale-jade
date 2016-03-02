<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class ExpansionToken extends TokenBase
{

    private $space = false;

    public function hasSpace()
    {

        return $this->space;
    }

    public function setHasSpace($space)
    {

        $this->space = $space;

        return $this;
    }
}