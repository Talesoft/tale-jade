<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;
use Tale\Jade\Lexer\Token\IndentToken;
use Tale\Jade\Lexer\Token\OutdentToken;

class TextBlockScanner implements ScannerInterface
{
    public function scan(State $state)
    {
        $reader = $state->getReader();

        foreach ($state->scan(TextScanner::class) as $token)
            yield $token;

        foreach ($state->scan(NewLineScanner::class) as $token)
            yield $token;

        $level = 0;
        while ($reader->hasLength()) {

            foreach ($state->loopScan([IndentationScanner::class, NewLineScanner::class]) as $token) {

                if ($token instanceof IndentToken)
                    $level++;

                if ($token instanceof OutdentToken)
                    $level--;

                yield $token;
            }

            if ($level <= 0)
                break;

            foreach ($state->scan(TextScanner::class) as $token)
                yield $token;
        }
    }
}