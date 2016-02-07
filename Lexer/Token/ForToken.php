<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;
use Tale\Jade\Util\SubjectTrait;

class ForToken extends TokenBase
{
    use SubjectTrait;

    protected function dump()
    {
        return $this->getSubject();
    }
}