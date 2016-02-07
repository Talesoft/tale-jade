<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;
use Tale\Jade\Lexer\Token\CaseToken;
use Tale\Jade\Lexer\Token\CodeToken;
use Tale\Jade\Lexer\Token\ExpressionToken;

class CodeScanner implements ScannerInterface
{

    public function scan(State $state)
    {

        $reader = $state->getReader();

        if (!$reader->peekChar('-'))
            return;

        /** @var CodeToken $token */
        $token = $state->createToken(CodeToken::class);
        $reader->consume();

        foreach ($state->scan(TextScanner::class) as $textToken) {

            yield $token;
            yield $textToken;
            return;
        }

        $token->setIsBlock(true);
        yield $token;

        foreach ($state->scan(TextBlockScanner::class) as $token) {

            yield $token;
        }
    }
}