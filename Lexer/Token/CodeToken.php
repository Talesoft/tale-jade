<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;
use Tale\Jade\Util\BlockTrait;
use Tale\Jade\Util\ValueTrait;

class CodeToken extends TokenBase
{
    use ValueTrait;
    use BlockTrait;
}