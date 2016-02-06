<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class TextToken extends TokenBase
{
    use ValueTrait;
    use EscapeTrait;

    protected function dump()
    {

        return [
            'value' => htmlentities($this->getValue(), ENT_QUOTES, 'UTF-8'),
            'escaped' => $this->isEscaped() ? 'yes' : 'no'
        ];
    }
}