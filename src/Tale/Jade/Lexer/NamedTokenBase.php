<?php

namespace Tale\Jade\Lexer;

use Tale\Jade\Lexer;

abstract class NamedTokenBase extends TokenBase
{

    private $_name;

    public function __construct(Lexer $lexer)
    {

        parent::__construct($lexer);

        $this->_name = null;
    }

    /**
     * @return string
     */
    public function getName()
    {

        return $this->_name;
    }

    public function export()
    {

        return [
            $this->_name
        ];
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {

        $this->_name = $name;

        return $this;
    }
}