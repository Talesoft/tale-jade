<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\NewLineToken;

class NewLineScanner implements ScannerInterface
{
    public function scan(Lexer $lexer)
    {
        $reader = $lexer->getReader();

        if (!$reader->peekNewLine())
            return;

        $token = $lexer->createToken(NewLineToken::class);

        $reader->consume();
        yield $token;
    }
}