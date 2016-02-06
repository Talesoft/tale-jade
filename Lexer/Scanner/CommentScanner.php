<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\CommentToken;

class CommentScanner implements ScannerInterface
{
    public function scan(Lexer $lexer)
    {

        $reader = $lexer->getReader();

        if (!$reader->peekString('//'))
            return;

        $reader->consume();

        /** @var CommentToken $token */
        $token = $lexer->createToken(CommentToken::class);

        if ($reader->peekChar('-')) {

            $reader->consume();
            $token->unrender();
        }

        yield $token;

        foreach ($lexer->scan(TextBlockScanner::class) as $token)
            yield $token;
    }
}