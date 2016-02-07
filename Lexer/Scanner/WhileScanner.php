<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\WhileToken;

class WhileScanner implements ScannerInterface
{

    public function scan(Lexer $lexer)
    {

        $scanner = new ControlStatementScanner(
            WhileToken::class,
            ['while']
        );

        foreach ($lexer->scan($scanner) as $token)
            yield $token;
    }
}