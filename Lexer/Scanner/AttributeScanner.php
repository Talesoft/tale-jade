<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\AttributeEndToken;
use Tale\Jade\Lexer\Token\AttributeStartToken;
use Tale\Jade\Lexer\Token\AttributeToken;

class AttributeScanner implements ScannerInterface
{

    public function scan(Lexer $lexer)
    {

        $reader = $lexer->getReader();

        if (!$reader->peekChar('('))
            return;

        $argSeparators = str_split(", \n\t");

        $reader->consume();

        yield $lexer->createToken(AttributeStartToken::class);

        $reader->readSpaces();

        if ($reader->peekChar(')')) {

            $reader->consume();
            yield $lexer->createToken(AttributeEndToken::class);
            return;
        }

        $continue = true;
        while ($reader->hasLength() && $continue) {

            if ($reader->peekString('//')) {

                //Comment line, ignore it.
                //There'd be no senseful way to either keep or
                //even output the comment afterwards, so we just omit it.
                $reader->readUntilNewLine();
                $reader->readSpaces();
                continue;
            }

            //We create the attribute token first (we don't need to yield it
            //but we fill it sequentially)
            /** @var AttributeToken $token */
            $token = $lexer->createToken(AttributeToken::class);
            $token->escape();

            $expr = $reader->readExpression(array_merge(
                $argSeparators,
                ['?!=', '?=', '!=', '=', ')']
            ));

            $hasValue = false;
            if ($reader->peekString('?!=')) {

                $token->unescape();
                $token->uncheck();
                $hasValue = true;
                $reader->consume();

            } else if ($reader->peekString('?=')) {

                $token->uncheck();
                $hasValue = true;
                $reader->consume();

            } else if ($reader->peekString('!=')) {

                $token->unescape();
                $hasValue = true;
                $reader->consume();

            } else if ($reader->peekChar('=')) {

                $hasValue = true;
                $reader->consume();
            }


            $token->setName($expr);

            if ($hasValue) {

                $expr = $reader->readExpression(array_merge(
                    $argSeparators,
                    [')']
                ));

                $token->setValue($expr);
            }

            yield $token;

            if (in_array($reader->peek(), $argSeparators, true)) {

                $reader->consume();
                $reader->readSpaces();

                $continue = !$reader->peekChar(')');
            } else {

                $continue = false;
            }
        }

        if (!$reader->peekChar(')'))
            $lexer->throwException(
                "Unclosed attribute block"
            );

        $reader->consume();
        yield $lexer->createToken(AttributeEndToken::class);

        foreach ($lexer->scan(ClassScanner::class) as $subToken)
            yield $subToken;

        foreach ($lexer->scan(SubScanner::class) as $subToken)
            yield $subToken;
    }
}