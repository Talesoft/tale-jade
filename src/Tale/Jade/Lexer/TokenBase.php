<?php

namespace Tale\Jade\Lexer;

use Tale\Jade\Lexer;

class TokenBase
{

    private $_lexer;
    private $_line;
    private $_offset;

    public function __construct(Lexer $lexer)
    {
        $this->_lexer = $lexer;
        $this->_line = $lexer->getLine();
        $this->_offset = $lexer->getOffset();
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