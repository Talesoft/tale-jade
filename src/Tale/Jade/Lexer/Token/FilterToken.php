<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer;

class FilterToken extends TextToken
{

    private $_filterName;

    public function __construct(Lexer $lexer)
    {

        parent::__construct($lexer);

        $this->_filterName = '';
    }

    public function getFilterName()
    {

        return $this->_filterName;
    }

    public function setFilterName($value)
    {

        $this->_filterName = $value;

        return $this;
    }
}