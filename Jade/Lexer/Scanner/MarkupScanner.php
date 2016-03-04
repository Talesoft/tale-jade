<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;

class MarkupScanner implements ScannerInterface
{
    public function scan(State $state)
    {

        $reader = $state->getReader();

        if (!$reader->peekChar('<'))
            return;

        foreach ($state->scan(TextScanner::class) as $token)
            yield $token;
    }
}