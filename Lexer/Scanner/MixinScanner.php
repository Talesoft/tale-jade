<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\MixinToken;

class MixinScanner implements ScannerInterface
{
    public function scan(Lexer $lexer)
    {

        foreach ($lexer->scanToken(
            MixinToken::class,
            "mixin[\t ]+(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*)"
        ) as $token) {

            yield $token;

            foreach ($lexer->scan(ClassScanner::class) as $subToken)
                yield $subToken;

            foreach ($lexer->scan(SubScanner::class) as $subToken)
                yield $subToken;
        }
    }
}