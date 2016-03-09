<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;
use Tale\Jade\Lexer\Token\AttributeEndToken;
use Tale\Jade\Lexer\Token\AttributeStartToken;
use Tale\Jade\Lexer\Token\AttributeToken;
use Tale\Reader;

class AttributeScanner implements ScannerInterface
{

    private function skipComments(Reader $reader)
    {

        if ($reader->peekString('//')) {

            $reader->consume();
            $reader->readUntilNewLine();
        }
    }

    public function scan(State $state)
    {

        $reader = $state->getReader();

        if (!$reader->peekChar('('))
            return;

        $reader->consume();

        yield $state->createToken(AttributeStartToken::class);

        if ($reader->peekChar(')')) {

            $reader->consume();
            yield $state->createToken(AttributeEndToken::class);
            return;
        }

        while ($reader->hasLength()) {

            //Check for comments
            // a( //Now attributes follow!
            //   a=a...
            $reader->readSpaces();
            $this->skipComments($reader);
            $reader->readSpaces();

            //We create the attribute token first (we don't need to yield it
            //but we fill it sequentially)
            /** @var AttributeToken $token */
            $token = $state->createToken(AttributeToken::class);
            $token->escape();
            $token->check();

            //Read the first part of the expression
            //e.g.:
            // (`a`), (`a`=b), (`$expr`, `$expr2`) (`$expr` `$expr`=a)
            $expr = $reader->readExpression([
                ' ', "\t", "\n", ',', '?!=', '?=', '!=', '=', ')', '//'
            ]);

            //Notice we have the following problem with spaces:
            //1. You can separate arguments with spaces
            // -> a(a=a b=b c=c)
            //2. You can have spaces around anything
            // -> a(a =
            //       a c=c d
            // =  d)
            //3. You can also separate with tabs and line-breaks
            // -> a(
            //      a=a
            //      b=b
            //      c=c
            //    )
            //
            //This leads to commas actually being just ignored as the most
            //simple solution. Attribute finding passes on as long as there's
            //no ) or EOF in sight.
            //TODO: Afaik this could also lead to
            // a(a=(b ? b : c)d=f), where a space or anything else _should_
            // be required.
            // Check this.

            //Ignore the comma. It's mainly just a "visual" separator,
            //it's actually completely optional.
            if ($reader->peekChar(','))
                $reader->consume();

            if (empty($expr))
                //An empty attribute would mean we did something like
                //,, or had a space before a comma (since space is also a valid
                //separator
                //We just skip that one.
                continue;


            $token->setName($expr);

            //Check for comments at this point
            // a(
            //      href //<- The name of the thing
            //      = 'value' //<- The value of the thing
            $reader->readSpaces();
            $this->skipComments($reader);
            $reader->readSpaces();

            //Check for our assignment-operators.
            //Notice that they have to be exactly written in the correct order
            //? first, ! second, = last (and required!)
            //It's made like this on purpose so that the Jade code is consistent
            //later on. It also makes this part of the lexing process easier and
            //more reliable.
            //If any of the following assignment operators have been found,
            //we REQUIRE a following expression as the attribute value
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

            //Check for comments again
            // a(
            //  href= //Here be value
            //      'value'
            //  )
            $reader->readSpaces();
            $this->skipComments($reader);
            $reader->readSpaces();

            if ($hasValue) {

                $expr = $reader->readExpression([
                    ' ', "\t", "\n", ',',  ')', '//'
                ]);
                $token->setValue($expr);

                //Ignore a comma if found
                if ($reader->peekChar(','))
                    $reader->consume();

                //And check for comments again
                // a(
                //  href='value' //<- Awesome attribute, i say
                //  )
                $reader->readSpaces();
                $this->skipComments($reader);
                $reader->readSpaces();
            }

            yield $token;

            if (!$reader->peekChar(')'))
                continue;

            break;
        }

        if (!$reader->peekChar(')'))
            $state->throwException(
                "Unclosed attribute block"
            );

        $reader->consume();
        yield $state->createToken(AttributeEndToken::class);

        foreach ($state->scan(ClassScanner::class) as $subToken)
            yield $subToken;

        foreach ($state->scan(SubScanner::class) as $subToken)
            yield $subToken;
    }
}