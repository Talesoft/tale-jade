<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;
use Tale\Jade\Lexer\Token\TextToken;

class SubScanner implements ScannerInterface
{
    public function scan(State $state)
    {

        $reader = $state->getReader();

        //Text block on tags etc. (p. some text|p!. some text)
        if ($reader->match('([!]?)\.')) {

            $escape = $reader->getMatch(1) === '!';
            $reader->consume();

            foreach ($state->scan(TextBlockScanner::class) as $token) {

                if ($token instanceof TextToken && $escape)
                    $token->escape();

                yield $token;
            }
        }

        //Escaped text after e.g. tags, classes (p! some text)
        if ($reader->peekChar('!')) {

            $reader->consume();

            foreach ($state->scan(TextScanner::class) as $token) {

                $token->escape();
                yield $token;
            }
        }

        foreach ($state->scan(ExpansionScanner::class) as $token)
            yield $token;
    }
}