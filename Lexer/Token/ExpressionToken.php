<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class ExpressionToken extends TokenBase
{
    use ValueTrait;
    use EscapeTrait;
    use CheckTrait;
}