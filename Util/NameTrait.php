<?php

namespace Tale\Jade\Util;

trait NameTrait
{

    private $_name = null;

    public function getName()
    {

        return $this->_name;
    }

    public function setName($name)
    {

        $this->_name = $name;

        return $this;
    }
}