<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\DoctypeToken;

class DoctypeScanner implements ScannerInterface
{

    public function scan(Lexer $lexer)
    {

        return $lexer->scanToken(DoctypeToken::class, "(doctype|!!!) (?<name>[^\n]*)");
    }
}