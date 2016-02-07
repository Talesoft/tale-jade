<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;
use Tale\Jade\Lexer\Token\TextToken;

class TextLineScanner implements ScannerInterface
{
    public function scan(State $state)
    {

        $reader = $state->getReader();

        if (!$reader->match('([!]?)\|'))
            return;

        $escaped = $reader->getMatch(1) === '!';

        $reader->consume();

        foreach ($state->scan(TextBlockScanner::class) as $token) {

            if ($escaped && $token instanceof TextToken)
                $token->escape();

            yield $token;
        }
    }
}