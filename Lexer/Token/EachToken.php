<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;
use Tale\Jade\Util\PairTrait;
use Tale\Jade\Util\SubjectTrait;

class EachToken extends TokenBase
{
    use SubjectTrait;
    use PairTrait;

    protected function dump()
    {
        return sprintf(
            "%s, %s in %s",
            $this->getItem() ?: '[No item name]',
            $this->getKey() ?: '[No key name]',
            $this->getSubject() ?: '[No subject]'
        );
    }
}