<?php

namespace Tale\Jade\Util;

trait EscapeTrait
{

    private $_escape = false;

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