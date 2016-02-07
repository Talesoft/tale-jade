<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class ClassToken extends TokenBase
{
    use NameTrait;

    protected function dump()
    {
        return [
            'name' => $this->getName()
        ];
    }
}