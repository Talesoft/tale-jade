<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\ConditionalToken;

class ConditionalScanner implements ScannerInterface
{

    public function scan(Lexer $lexer)
    {

        $scanner = new ControlStatementScanner(
            ConditionalToken::class,
            ['if', 'unless', 'else[ \t]*if', 'else']
        );

        foreach ($lexer->scan($scanner) as $token) {

            if ($token instanceof ConditionalToken)
                $token->setName(preg_replace('/[ \t]/', '', $token->getName()));

            yield $token;
        }
    }
}