<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer;

class AttributeToken extends ExpressionToken
{

    private $_name;

    public function __construct(Lexer $lexer)
    {

        parent::__construct($lexer);

        $this->_name = false;
    }

    /**
     * @return string
     */
    public function getName()
    {

        return $this->_name;
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

    public function export()
    {

        return [$this->_name => $this->getValue()];
    }
}