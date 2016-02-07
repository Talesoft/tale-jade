<?php

namespace Tale\Jade\Util;

trait ModeTrait
{

    private $_mode = null;

    public function getMode()
    {

        return $this->_mode;
    }

    public function setMode($mode)
    {

        $this->_mode = $mode;

        return $this;
    }
}