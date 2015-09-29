<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer;

class CommentToken extends TextToken
{

    private $_render;

    public function __construct(Lexer $lexer)
    {

        parent::__construct($lexer);

        $this->_render = true;
    }

    public function isRendered()
    {

        return $this->_render;
    }

    public function render()
    {

        $this->_render = true;

        return $this;
    }

    public function unrender()
    {

        $this->_render = false;

        return $this;
    }
}