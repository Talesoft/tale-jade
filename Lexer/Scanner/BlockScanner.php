<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;
use Tale\Jade\Lexer\Token\BlockToken;

class BlockScanner implements ScannerInterface
{
    public function scan(State $state)
    {

        foreach ($state->scanToken(
            BlockToken::class,
            'block(?:[\t ]+(?<mode>append|prepend|replace))?(?:[\t ]+(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*))?'
        ) as $token) {

            yield $token;

            foreach ($state->scan(SubScanner::class) as $subToken)
                yield $subToken;
        }

        foreach ($state->scanToken(
            BlockToken::class,
            '(?<mode>append|prepend|replace)(?:[\t ]+(?<name>[a-zA-ZA-Z][a-zA-Z0-9\-_]*))'
        ) as $token) {

            yield $token;

            foreach ($state->scan(SubScanner::class) as $subToken)
                yield $subToken;
        }
    }
}