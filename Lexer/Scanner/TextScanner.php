<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\TextToken;

class TextScanner implements ScannerInterface
{
    public function scan(Lexer $lexer)
    {

        $reader = $lexer->getReader();

        /** @var TextToken $token */
        $token = $lexer->createToken(TextToken::class);
        $text = trim($reader->readUntilNewLine());

        if (Lexer\safe_strlen($text) < 1)
            return;

        $token->setValue($text);
        yield $token;
    }
}