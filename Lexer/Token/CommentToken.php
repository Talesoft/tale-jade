<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;
use Tale\Jade\Util\VisibleTrait;

class CommentToken extends TokenBase
{
    use VisibleTrait;

    protected function dump()
    {
        return $this->isVisible() ? 'rendered' : 'hidden';
    }
}