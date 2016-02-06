<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\ExpansionToken;

class ExpansionScanner implements ScannerInterface
{
    public function scan(Lexer $lexer)
    {

        $reader = $lexer->getReader();

        if (!$reader->peekChar(':'))
            return;

        $reader->consume();
        /** @var ExpansionToken $token */
        $token = $lexer->createToken(ExpansionToken::class);

        $spaces = $reader->readIndentation();
        $token->setHasSpace($spaces !== null);

        yield $token;
    }
}