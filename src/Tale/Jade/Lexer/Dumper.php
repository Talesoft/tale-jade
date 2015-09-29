<?php

namespace Tale\Jade\Lexer;

class Dumper
{

    private $_lexer;

    public function __construct(Lexer $lexer)
    {

        $this->_lexer = $lexer;
    }

    public function dump($input)
    {

        $tokens = $this->_lexer->lex($input);

        foreach ($tokens as $token)
            $this->dumpToken($token);
    }

    public function dumpToken(TokenBase $token)
    {
        echo "[$token->type";

        switch($token->type) {
            case 'indent':
            case 'outdent':
                echo " $token->levels";
                break;
            case 'extends':
            case 'include':

                if ($token->filter)
                    echo " $token->filter";

                echo " $token->path";
                break;
            case 'tag':
            case 'class':
            case 'id':
            case 'filter':
            case 'mixin':
            case 'mixin-call':
                echo " $token->name";
                break;
            case 'block':
                echo " $token->mode".($token->name ? " $token->name" : '');
                break;
            case 'text':
                echo ' '.str_replace("\n", '\n', $token->content);
                break;
            case 'comment':
            case 'code':
                echo $token->escaped ? ' escaped' : '';
                break;
            case 'doctype':
                echo " $token->value";
                break;
            case 'case':
            case 'if':
            case 'elseif':
            case 'else':
            case 'unless':
            case 'each':
            case 'while':

                if ($token->subject)
                    echo " $token->subject";

                if ($token->type === 'each' && $token->itemName)
                    echo " $token->itemName";

                if ($token->type === 'each' && $token->keyName)
                    echo " $token->keyName";
                break;
            case 'when':
                echo " $token->value";
                break;
            case 'attribute':

                if (isset($token->name))
                    echo " $token->name";

                if ($token->value)
                    echo " $token->value";
                break;
        }

        echo "]";

        if ($token->type === 'newLine')
            echo "\n";
    }
}