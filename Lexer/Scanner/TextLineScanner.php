<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\TextToken;

class TextLineScanner implements ScannerInterface
{
    public function scan(Lexer $lexer)
    {

        $reader = $lexer->getReader();

        if (!$reader->match('([!]?)\|'))
            return;

        $escaped = $reader->getMatch(1) === '!';

        $reader->consume();

        foreach ($lexer->scan(TextBlockScanner::class) as $token) {

            if ($escaped && $token instanceof TextToken)
                $token->escape();

            yield $token;
        }
    }
}