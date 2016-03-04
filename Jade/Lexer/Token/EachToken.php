<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;
use Tale\Jade\Util\PairTrait;
use Tale\Jade\Util\SubjectTrait;

class EachToken extends TokenBase
{
    use SubjectTrait;
    use PairTrait;
}