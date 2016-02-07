<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class TagToken extends TokenBase
{
    use NameTrait;

    protected function dump()
    {
        return [
            'name' => $this->getName()
        ];
    }
}