<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\TokenBase;

class TextToken extends TokenBase
{

    private $_value;

    public function __construct(Lexer $lexer)
    {

        parent::__construct($lexer);

        $this->_value = '';
    }

    public function getValue()
    {

        return $this->_value;
    }

    public function setValue($value)
    {

        $this->_value = $value;

        return $this;
    }

    protected function export()
    {

        return [$this->_value];
    }
}