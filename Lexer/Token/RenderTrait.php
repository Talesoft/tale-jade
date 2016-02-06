<?php

namespace Tale\Jade\Lexer\Token;

trait RenderTrait
{

    private $_rendered = true;

    public function isRendered()
    {

        return $this->_rendered;
    }

    public function render()
    {

        $this->_rendered = true;

        return $this;
    }

    public function unrender()
    {

        $this->_rendered = false;

        return $this;
    }
}