<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\WhenToken;

class WhenScanner implements ScannerInterface
{

    public function scan(Lexer $lexer)
    {

        $scanner = new ControlStatementScanner(
            WhenToken::class,
            ['when', 'default']
        );

        foreach ($lexer->scan($scanner) as $token)
            yield $token;
    }
}