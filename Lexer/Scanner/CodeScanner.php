<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\CaseToken;
use Tale\Jade\Lexer\Token\CodeToken;
use Tale\Jade\Lexer\Token\ExpressionToken;

class CodeScanner implements ScannerInterface
{

    public function scan(Lexer $lexer)
    {

        $reader = $lexer->getReader();

        if (!$reader->peekChar('-'))
            return;

        /** @var CodeToken $token */
        $token = $lexer->createToken(CodeToken::class);
        $reader->consume();

        foreach ($lexer->scan(TextScanner::class) as $textToken) {

            yield $token;
            yield $textToken;
            return;
        }

        $token->setIsBlock(true);
        yield $token;

        foreach ($lexer->scan(TextBlockScanner::class) as $token) {

            yield $token;
        }
    }
}