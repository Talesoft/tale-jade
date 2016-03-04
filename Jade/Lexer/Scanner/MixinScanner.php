<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;
use Tale\Jade\Lexer\Token\MixinToken;

class MixinScanner implements ScannerInterface
{
    public function scan(State $state)
    {

        foreach ($state->scanToken(
            MixinToken::class,
            "mixin[\t ]+(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*)"
        ) as $token) {

            yield $token;

            foreach ($state->scan(ClassScanner::class) as $subToken)
                yield $subToken;

            foreach ($state->scan(SubScanner::class) as $subToken)
                yield $subToken;
        }
    }
}