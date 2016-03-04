<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;
use Tale\Jade\Lexer\Token\ExpressionToken;

class ExpressionScanner implements ScannerInterface
{

    public function scan(State $state)
    {

        $reader = $state->getReader();

        if (!$reader->match('[\t ]*[?]?[!]?=[\t ]*'))
            return;

        $prefix = $reader->consume();

        /** @var ExpressionToken $token */
        $token = $state->createToken(ExpressionToken::class);

        if (Lexer\safe_strpos($prefix, '!') !== false)
            $token->escape();

        if (Lexer\safe_strpos($prefix, '?') !== false)
            $token->uncheck();

        $token->setValue($reader->readExpression(["\n", '//']));

        yield $token;
    }
}