<?php

namespace Tale\Jade\Lexer\Dumper;

use Tale\Jade\Lexer\DumperBase;
use Tale\Jade\Lexer\Token\AttributeEndToken;
use Tale\Jade\Lexer\Token\AttributeStartToken;
use Tale\Jade\Lexer\Token\AttributeToken;
use Tale\Jade\Lexer\Token\ExpressionToken;
use Tale\Jade\Lexer\Token\IndentToken;
use Tale\Jade\Lexer\Token\NewLineToken;
use Tale\Jade\Lexer\Token\OutdentToken;
use Tale\Jade\Lexer\Token\TextToken;
use Tale\Jade\Lexer\TokenInterface;

class Text extends DumperBase
{

    protected function dumpToken(TokenInterface $token)
    {

        $text = '';
        $suffix = '';
        switch (get_class($token)) {
            case IndentToken::class:
                $text = '->';
                break;
            case OutdentToken::class:
                $text = '<-';
                break;
            case NewLineToken::class:
                $text = '\n';
                $suffix = "\n";
                break;
            case AttributeStartToken::class:
                $text = '(';
                break;
            case AttributeToken::class:
                /** @var AttributeToken $token */
                $text = sprintf(
                    "Attr %s=%s (%s, %s)",
                    $token->getName() ?: '""',
                    $token->getValue() ?: '""',
                    $token->isEscaped() ? 'escaped' : 'unescaped',
                    $token->isChecked() ? 'checked' : 'unchecked'
                );
                break;
            case AttributeEndToken::class:
                $text = ')';
                break;
            case TextToken::class:
                /** @var TextToken $token */
                $text = 'Text '.$token->getValue();
                break;
            case ExpressionToken::class:
                /** @var ExpressionToken $token */
                $text = sprintf(
                    "Expr %s (%s, %s)",
                    $token->getValue() ?: '""',
                    $token->isEscaped() ? 'escaped' : 'unescaped',
                    $token->isChecked() ? 'checked' : 'unchecked'
                );
                break;
            default:

                $text = $this->getTokenName($token);
                break;
        }

        return "[$text]".$suffix;
    }
}