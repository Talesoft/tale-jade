<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;
use Tale\Jade\Lexer\Token\DoctypeToken;

class DoctypeScanner implements ScannerInterface
{

    public function scan(State $state)
    {

        return $state->scanToken(DoctypeToken::class, "(doctype|!!!) (?<name>[^\n]*)");
    }
}