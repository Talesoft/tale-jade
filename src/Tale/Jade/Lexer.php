<?php

namespace Tale\Jade;

use Tale\Jade\Node\DocumentNode;

class Lexer
{

    private $_input;
    private $_length;
    private $_line;
    private $_offset;
    private $_level;

    private $_stack;
    private $_output;

    public function __construct(array $options = null)
    {

        $this->_options = array_replace([

        ], $options ? $options : []);
    }


    public function lex($input)
    {

        $this->_input = $input;
        $this->_length = strlen($this->_input);
        $this->_line = 0;
        $this->_offset = 0;
        $this->_level = 0;

        $this->_stack = [];
        $this->_output = new DocumentNode();

        $lines = array_map('rtrim', explode("\n", $input));

        foreach ($lines as $line) {

            $indent =
        }
    }

    protected function readWhile()
}