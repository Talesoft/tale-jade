<?php

namespace Tale\Jade\Lexer\Token;

trait TypeTrait
{

    private $_type = null;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param string $type
     *
     * @return TypeTrait
     */
    public function setType($type)
    {
        $this->_type = $type;

        return $this;
    }
}