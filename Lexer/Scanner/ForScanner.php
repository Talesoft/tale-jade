<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\ForToken;

class ForScanner implements ScannerInterface
{

    public function scan(Lexer $lexer)
    {

        $scanner = new ControlStatementScanner(
            ForToken::class,
            ['for']
        );

        foreach ($lexer->scan($scanner) as $token)
            yield $token;
    }
}