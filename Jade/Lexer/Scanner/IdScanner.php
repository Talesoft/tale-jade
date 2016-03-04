<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;
use Tale\Jade\Lexer\Token\IdToken;

class IdScanner implements ScannerInterface
{

    public function scan(State $state)
    {

        foreach ($state->scanToken(IdToken::class, '#(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*)', 'i') as $token) {

            yield $token;

            foreach ($state->scan(ClassScanner::class) as $subToken)
                yield $subToken;

            foreach ($state->scan(SubScanner::class) as $subToken)
                yield $subToken;
        }
    }
}