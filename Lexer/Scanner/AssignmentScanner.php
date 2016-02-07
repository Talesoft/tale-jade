<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\AssignmentToken;

class AssignmentScanner implements ScannerInterface
{

    public function scan(Lexer $lexer)
    {

        return $lexer->scanToken(
            AssignmentToken::class,
            '&(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*)'
        );
    }
}