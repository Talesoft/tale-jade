<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;
use Tale\Jade\Util\CheckTrait;
use Tale\Jade\Util\EscapeTrait;
use Tale\Jade\Util\NameTrait;
use Tale\Jade\Util\ValueTrait;

class AttributeToken extends TokenBase
{
    use NameTrait;
    use ValueTrait;
    use EscapeTrait;
    use CheckTrait;
}