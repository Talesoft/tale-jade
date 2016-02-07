<?php

namespace Tale\Jade\Util;

trait EscapeTrait
{

    private $_escaped = false;

    public function isEscaped()
    {

        return $this->_escaped;
    }

    public function setIsEscaped($escaped)
    {

        $this->_escaped = $escaped;

        return $this;
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