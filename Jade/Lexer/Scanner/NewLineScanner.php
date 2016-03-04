<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;
use Tale\Jade\Lexer\Token\NewLineToken;

class NewLineScanner implements ScannerInterface
{
    public function scan(State $state)
    {
        $reader = $state->getReader();

        if (!$reader->peekNewLine())
            return;

        $token = $state->createToken(NewLineToken::class);

        $reader->consume();
        yield $token;
    }
}