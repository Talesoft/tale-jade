<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class IndentToken extends TokenBase
{

    private $_levels;

    public function getLevels()
    {

        return $this->_levels;
    }

    public function setLevels($levels)
    {

        $this->_levels = $levels;

        return $this;
    }
}