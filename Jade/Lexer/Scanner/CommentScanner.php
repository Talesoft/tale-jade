<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;
use Tale\Jade\Lexer\Token\CommentToken;

class CommentScanner implements ScannerInterface
{
    public function scan(State $state)
    {

        $reader = $state->getReader();

        if (!$reader->peekString('//'))
            return;

        $reader->consume();

        /** @var CommentToken $token */
        $token = $state->createToken(CommentToken::class);

        if ($reader->peekChar('-')) {

            $reader->consume();
            $token->hide();
        }

        yield $token;

        foreach ($state->scan(TextBlockScanner::class) as $token)
            yield $token;
    }
}