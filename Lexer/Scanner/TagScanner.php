<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\TagToken;

class TagScanner implements ScannerInterface
{

    public function scan(Lexer $lexer)
    {

        foreach ($lexer->scanToken(TagToken::class, '(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*)', 'i') as $token) {

            yield $token;

            foreach ($lexer->scan(ClassScanner::class) as $subToken)
                yield $subToken;

            foreach ($lexer->scan(SubScanner::class) as $subToken)
                yield $subToken;
        }
    }
}