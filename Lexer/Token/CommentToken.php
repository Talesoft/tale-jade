<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class CommentToken extends TokenBase
{
    use RenderTrait;

    protected function dump()
    {
        return [
            'rendered' => $this->isRendered() ? 'yes' : 'no'
        ];
    }
}