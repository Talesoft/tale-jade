<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;
use Tale\Jade\Lexer\Token\ImportToken;

class ImportScanner implements ScannerInterface
{
    public function scan(State $state)
    {

        return $state->scanToken(
            ImportToken::class,
            '(?<name>extends|include)(?::(?<filter>[a-zA-Z_][a-zA-Z0-9\-_]*))?[\t ]+(?<path>[a-zA-Z0-9\-_\\/\. ]+)'
        );
    }
}