<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\CommentToken;
use Tale\Jade\Lexer\Token\FilterToken;

class FilterScanner implements ScannerInterface
{
    public function scan(Lexer $lexer)
    {

        foreach ($lexer->scanToken(FilterToken::class, ':(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*)') as $token) {

            yield $token;

            foreach ($lexer->scan(TextBlockScanner::class) as $subToken)
                yield $subToken;
        }
    }
}