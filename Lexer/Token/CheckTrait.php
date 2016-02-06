<?php

namespace Tale\Jade\Lexer\Token;

trait CheckTrait
{

    private $_checked = true;

    public function isChecked()
    {

        return $this->_checked;
    }

    public function check()
    {

        $this->_checked = true;

        return $this;
    }

    public function uncheck()
    {

        $this->_checked = false;

        return $this;
    }
}