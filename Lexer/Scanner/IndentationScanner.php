<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\Token\IndentToken;
use Tale\Jade\Lexer\Token\OutdentToken;

class IndentationScanner implements ScannerInterface
{
    public function scan(Lexer $lexer)
    {

        $reader = $lexer->getReader();

        if ($reader->getOffset() !== 0)
            return;

        $indent = $reader->readIndentation();

        //If this is an empty line, we ignore the indentation completely.
        foreach ($lexer->scan(NewLineScanner::class) as $token) {

            yield $token;
            return;
        }

        $oldLevel = $lexer->getLevel();
        if ($indent === null)
            $lexer->setLevel(0);
        else {

            $spaces = Lexer\safe_strpos($indent, ' ') !== false;
            $tabs = Lexer\safe_strpos($indent, "\t") !== false;
            $mixed = $spaces && $tabs;

            if ($mixed) {

                switch ($lexer->getIndentStyle()) {
                    case Lexer::INDENT_SPACE:
                    default:

                        //Convert tabs to spaces based on indentWidth
                        $spaces = str_replace(Lexer::INDENT_TAB, str_repeat(
                            Lexer::INDENT_SPACE,
                            $lexer->getIndentWidth() ? $lexer->getIndentWidth() : 4
                        ), $spaces);
                        $tabs = false;
                        $mixed = false;
                        break;
                    case Lexer::INDENT_TAB:

                        //Convert spaces to tabs based on indentWidth
                        $spaces = str_replace(Lexer::INDENT_SPACE, str_repeat(
                            Lexer::INDENT_TAB,
                            $lexer->getIndentWidth() ? $lexer->getIndentWidth() : 1
                        ), $spaces);
                        $spaces = false;
                        $mixed = false;
                        break;
                }
            }

            //Validate the indentation style
            $lexer->setIndentStyle($tabs ? Lexer::INDENT_TAB : Lexer::INDENT_SPACE);

            //Validate the indentation width
            if (!$lexer->getIndentWidth())
                //We will use the pretty first indentation as our indent width
                $lexer->setIndentWidth(Lexer\safe_strlen($indent));

            $lexer->setLevel(intval(round(Lexer\safe_strlen($indent) / $lexer->getIndentWidth())));

            if ($lexer->getLevel() > $oldLevel + 1)
                $lexer->setLevel($oldLevel + 1);
        }

        $levels = $lexer->getLevel() - $oldLevel;

        //Unchanged levels
        if ($levels === 0)
            return;

        //We create a token for each indentation/outdentation
        $type = $levels > 0 ? IndentToken::class : OutdentToken::class;
        $levels = abs($levels);

        while ($levels--)
            yield $lexer->createToken($type);
    }
}