<?php

namespace Tale\Jade\Lexer;

use Tale\Jade\Lexer;

interface ScannerInterface
{

    public function scan(Lexer $lexer);
}