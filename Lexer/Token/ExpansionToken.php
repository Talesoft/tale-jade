<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class ExpansionToken extends TokenBase
{

    private $_space = false;

    public function hasSpace()
    {

        return $this->_space;
    }

    public function setHasSpace($space)
    {

        $this->_space = $space;

        return $this;
    }
}