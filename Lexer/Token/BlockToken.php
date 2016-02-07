<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;
use Tale\Jade\Util\ModeTrait;
use Tale\Jade\Util\NameTrait;

class BlockToken extends TokenBase
{
    use NameTrait;
    use ModeTrait;

    protected function dump()
    {
        return sprintf(
            "%s (%s)",
            $this->getName() ?: '[No name]',
            $this->getMode() ?: '[No mode]'
        );
    }


}