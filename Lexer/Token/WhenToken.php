<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class WhenToken extends TokenBase
{
    use NameTrait;
    use SubjectTrait;

    private $_default = false;

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->_default;
    }

    /**
     * @param bool $default
     *
     * @return BlockToken
     */
    public function setIsDefault($default)
    {
        $this->_default = $default;

        return $this;
    }
}