<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\DoToken;

class DoScanner implements ScannerInterface
{

    public function scan(Lexer $lexer)
    {

        $scanner = new ControlStatementScanner(
            DoToken::class,
            ['do']
        );

        foreach ($lexer->scan($scanner) as $token)
            yield $token;
    }
}