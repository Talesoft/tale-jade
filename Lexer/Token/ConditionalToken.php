<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class ConditionalToken extends TokenBase
{
    use TypeTrait;
    use SubjectTrait;
}