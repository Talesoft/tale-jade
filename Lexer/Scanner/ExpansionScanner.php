<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;
use Tale\Jade\Lexer\Token\ExpansionToken;

class ExpansionScanner implements ScannerInterface
{
    public function scan(State $state)
    {

        $reader = $state->getReader();

        if (!$reader->peekChar(':'))
            return;

        $reader->consume();
        /** @var ExpansionToken $token */
        $token = $state->createToken(ExpansionToken::class);

        $spaces = $reader->readIndentation();
        $token->setHasSpace($spaces !== null);

        yield $token;
    }
}