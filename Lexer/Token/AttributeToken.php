<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class AttributeToken extends TokenBase
{
    use NameTrait;
    use ValueTrait;
    use EscapeTrait;
    use CheckTrait;
}