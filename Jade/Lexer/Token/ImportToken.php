<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;
use Tale\Jade\Util\FilterTrait;
use Tale\Jade\Util\NameTrait;
use Tale\Jade\Util\PathTrait;

class ImportToken extends TokenBase
{
    use NameTrait;
    use PathTrait;
    use FilterTrait;
}