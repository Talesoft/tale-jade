<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;

class MarkupScanner implements ScannerInterface
{
    public function scan(Lexer $lexer)
    {

        $reader = $lexer->getReader();

        if (!$reader->peekChar('<'))
            return;

        foreach ($lexer->scan(TextScanner::class) as $token)
            yield $token;
    }
}