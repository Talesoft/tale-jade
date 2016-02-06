<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\BlockToken;

class BlockScanner implements ScannerInterface
{
    public function scan(Lexer $lexer)
    {

        foreach ($lexer->scanToken(
            BlockToken::class,
            'block(?:[\t ]+(?<mode>append|prepend|replace))?(?:[\t ]+(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*))?'
        ) as $token) {

            yield $token;

            foreach ($lexer->scan(SubScanner::class) as $subToken)
                yield $subToken;
        }

        foreach ($lexer->scanToken(
            BlockToken::class,
            '(?<mode>append|prepend|replace)(?:[\t ]+(?<name>[a-zA-ZA-Z][a-zA-Z0-9\-_]*))'
        ) as $token) {

            yield $token;

            foreach ($lexer->scan(SubScanner::class) as $subToken)
                yield $subToken;
        }
    }
}