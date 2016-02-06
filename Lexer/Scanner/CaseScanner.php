<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\CaseToken;

class CaseScanner implements ScannerInterface
{

    public function scan(Lexer $lexer)
    {

        $scanner = new ControlStatementScanner(
            CaseToken::class,
            ['case']
        );

        foreach ($lexer->scan($scanner) as $token)
            yield $token;
    }
}