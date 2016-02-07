<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class CodeToken extends TokenBase
{
    use ValueTrait;

    private $_block = false;

    public function isBlock()
    {

        return $this->_block;
    }

    public function setIsBlock($block)
    {

        $this->_block = $block;

        return $this;
    }

    protected function dump()
    {
        return [
            'block' => $this->isBlock() ? 'yes' : 'no'
        ];
    }
}