<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;
use Tale\Jade\Util\EscapeTrait;
use Tale\Jade\Util\ValueTrait;

class TextToken extends TokenBase
{
    use ValueTrait;
    use EscapeTrait;

    protected function dump()
    {

        return sprintf(
            "%s (%s)",
            $this->getValue() ?: '[No value]',
            $this->isEscaped() ? 'escaped' : 'unescaped'
        );
    }
}