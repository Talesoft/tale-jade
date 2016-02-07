<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\MixinCallToken;

class MixinCallScanner implements ScannerInterface
{
    public function scan(Lexer $lexer)
    {

        foreach ($lexer->scanToken(
            MixinCallToken::class,
            '\+(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*)'
        ) as $token) {

            yield $token;

            foreach ($lexer->scan(ClassScanner::class) as $subToken)
                yield $subToken;

            foreach ($lexer->scan(SubScanner::class) as $subToken)
                yield $subToken;
        }
    }
}