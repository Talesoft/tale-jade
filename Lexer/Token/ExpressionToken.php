<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;
use Tale\Jade\Util\CheckTrait;
use Tale\Jade\Util\EscapeTrait;
use Tale\Jade\Util\ValueTrait;

class ExpressionToken extends TokenBase
{
    use ValueTrait;
    use EscapeTrait;
    use CheckTrait;

    protected function dump()
    {

        return sprintf(
            "%s (%s, %s)",
            $this->getValue() ?: '[No value]',
            $this->isEscaped() ? 'escaped' : 'unescaped',
            $this->isChecked() ? 'checked' : 'unchecked'
        );
    }
}