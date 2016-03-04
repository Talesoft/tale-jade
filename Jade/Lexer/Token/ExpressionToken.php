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
}