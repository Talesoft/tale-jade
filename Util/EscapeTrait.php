<?php

namespace Tale\Jade\Util;

trait EscapeTrait
{

    private $escaped = false;

    public function isEscaped()
    {

        return $this->escaped;
    }

    public function setIsEscaped($escaped)
    {

        $this->escaped = $escaped;

        return $this;
    }

    public function escape()
    {

        $this->escaped = true;

        return $this;
    }

    public function unescape()
    {

        $this->escaped = false;

        return $this;
    }
}