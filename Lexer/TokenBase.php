<?php

namespace Tale\Jade\Lexer;

use Tale\Jade\Util\LevelGetTrait;

abstract class TokenBase implements TokenInterface
{
    use LevelGetTrait;

    private $_line;
    private $_offset;

    public function __construct($line = null, $offset = null, $level = null)
    {

        $this->_line = $line ?: 0;
        $this->_offset = $offset ?: 0;
        $this->_level = $level ?: 0;
    }

    /**
     * @return int
     */
    public function getLine()
    {
        return $this->_line;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->_offset;
    }
}