<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\ExpressionToken;

class ExpressionScanner implements ScannerInterface
{

    public function scan(Lexer $lexer)
    {

        $reader = $lexer->getReader();

        if (!$reader->match('\??!?=?[\t ]*'))
            return;

        $prefix = $reader->consume();

        /** @var ExpressionToken $token */
        $token = $lexer->createToken(ExpressionToken::class);

        if (Lexer\safe_strpos($prefix, '!') !== false)
            $token->escape();

        if (Lexer\safe_strpos($prefix, '?') !== false)
            $token->check();

        $token->setValue($reader->readExpression(["\n", '//']));

        yield $token;
    }
}