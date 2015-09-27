<?php

namespace Tale\Jade\Lexer;

class Token
{

    public $type;
    public $line;
    public $offset;

    public function __construct($type, $line, $offset)
    {

        $this->type = $type;
        $this->line = $line;
        $this->offset = $offset;
    }
}