<?php

namespace Tale\Jade\Lexer\Token;

trait EscapeTrait
{

    private $_escaped = false;

    public function isEscaped()
    {

        return $this->_escaped;
    }

    public function escape()
    {

        $this->_escaped = true;

        return $this;
    }

    public function unescape()
    {

        $this->_escaped = false;

        return $this;
    }
}