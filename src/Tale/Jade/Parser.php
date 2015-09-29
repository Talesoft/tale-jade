<?php

namespace Tale\Jade;

use Tale\Jade\Lexer\Token\IndentToken;
use Tale\Jade\Lexer\Token\NewLineToken;
use Tale\Jade\Lexer\TokenBase;

class Parser
{

    private $_options;
    private $_lexer;

    private $_blocks = [];
    private $_subject = null;
    private $_node = null;

    public function __construct(array $options = null, Lexer $lexer = null)
    {

        $this->_options = array_replace([
            'lexer' => []
        ], $options ? $options : []);
        $this->_lexer = $lexer ? $lexer : new Lexer($this->_options['lexer']);
    }

    public function parse($input)
    {

        foreach ($this->_lexer->lex($input) as $token) {

            $method = 'handle'.substr(basename(get_class($token)), 0, -5);

            call_user_func([$this, $method], $token);
        }
    }

    protected function handleNewLine(NewLineToken $token)
    {

        $this->_subject = null;
    }

    protected function handleIndent(IndentToken $token)
    {

        $this->_node = $this->_subject;
    }
}