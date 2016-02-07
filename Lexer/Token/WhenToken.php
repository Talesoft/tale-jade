<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class WhenToken extends TokenBase
{
    use NameTrait;
    use SubjectTrait;

    protected function dump()
    {
        return [
            'name' => $this->getName(),
            'subject' => $this->getSubject()
        ];
    }
}