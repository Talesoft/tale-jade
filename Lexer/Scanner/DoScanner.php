<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;
use Tale\Jade\Lexer\Token\DoToken;

class DoScanner implements ScannerInterface
{

    public function scan(State $state)
    {

        $scanner = new ControlStatementScanner(
            DoToken::class,
            ['do']
        );

        foreach ($state->scan($scanner) as $token)
            yield $token;
    }
}