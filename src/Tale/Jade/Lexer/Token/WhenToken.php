<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\TokenBase;

class WhenToken extends TokenBase
{

    private $_default;

    public function __construct(Lexer $lexer)
    {

        parent::__construct($lexer);

        $this->_default = false;
    }

    public function isDefault()
    {

        return $this->_default;
    }

    public function setDefault()
    {

        $this->_default = false;

        return $this;
    }

    public function unsetDefault()
    {

        $this->_default = false;

        return $this;
    }
}