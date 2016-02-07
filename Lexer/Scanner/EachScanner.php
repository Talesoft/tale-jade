<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\EachToken;

class EachScanner implements ScannerInterface
{

    public function scan(Lexer $lexer)
    {

        $reader = $lexer->getReader();

        if (!$reader->match("each[\t ]+"))
            return;

        /** @var EachToken $token */
        $token = $lexer->createToken(EachToken::class);
        $reader->consume();

        if (!$reader->match(
            "\\$?(?<itemName>[a-zA-Z_][a-zA-Z0-9_]*)(?:[\t ]*,[\t ]*\\$?(?<keyName>[a-zA-Z_][a-zA-Z0-9_]*))?[\t ]+in[\t ]+"
        )) {

            $lexer->throwException(
                'The syntax for each is `each [$]itemName[, [$]keyName]] in [subject]`'
            );
        }

        $token->setItemName($reader->getMatch('itemName'));
        $token->setKeyName($reader->getMatch('keyName'));

        $reader->consume();

        $token->setSubject($reader->readExpression([':', "\n"]));

        yield $token;
    }
}