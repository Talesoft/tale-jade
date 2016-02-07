<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class ExpressionToken extends TokenBase
{
    use ValueTrait;
    use EscapeTrait;
    use CheckTrait;

    protected function dump()
    {

        return [
            'value' => $this->getValue(),
            'escaped' => $this->isEscaped() ? 'yes' : 'no',
            'checked' => $this->isChecked() ? 'yes' : 'no'
        ];
    }
}