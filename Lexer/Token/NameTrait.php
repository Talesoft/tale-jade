<?php

namespace Tale\Jade\Lexer\Token;

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