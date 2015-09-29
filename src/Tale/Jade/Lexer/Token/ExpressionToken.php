<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer;

class ExpressionToken extends TextToken
{

    private $_escape;

    public function __construct(Lexer $lexer)
    {

        parent::__construct($lexer);

        $this->_escape = false;
    }

    public function isEscaped()
    {

        return $this->_escape;
    }

    public function escape()
    {

        $this->_escape = true;

        return $this;
    }

    public function unescape()
    {

        $this->_escape = false;

        return $this;
    }
}