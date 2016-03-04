<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;
use Tale\Jade\Util\NameTrait;
use Tale\Jade\Util\SubjectTrait;

class CaseToken extends TokenBase
{
    use NameTrait;
    use SubjectTrait;
}