<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class AttributeToken extends TokenBase
{
    use NameTrait;
    use ValueTrait;
    use EscapeTrait;
    use CheckTrait;

    protected function dump()
    {

        return [
            'name' => $this->getName(),
            'value' => $this->getValue(),
            'escaped' => $this->isEscaped() ? 'yes' : 'no',
            'checked' => $this->isChecked() ? 'yes' : 'no'
        ];
    }
}