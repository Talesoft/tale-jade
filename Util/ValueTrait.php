<?php

namespace Tale\Jade\Util;

trait ValueTrait
{

    private $_value = null;

    public function getValue()
    {

        return $this->_value;
    }

    public function setValue($value)
    {

        $this->_value = $value;
    }
}