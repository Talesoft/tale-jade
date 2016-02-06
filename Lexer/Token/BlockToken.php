<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class BlockToken extends TokenBase
{
    use NameTrait;

    private $_mode = null;

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->_mode;
    }

    /**
     * @param string $mode
     *
     * @return BlockToken
     */
    public function setMode($mode)
    {
        $this->_mode = $mode;

        return $this;
    }

    protected function dump()
    {
        return [
            'name' => $this->getName(),
            'mode' => $this->getMode()
        ];
    }


}