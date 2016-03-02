<?php

namespace Tale\Jade\Lexer;

use Tale\Jade\Util\LevelGetTrait;
use Tale\Jade\Util\LineOffsetTrait;

abstract class TokenBase implements TokenInterface
{
    use LineOffsetTrait;
    use LevelGetTrait;

    public function __construct($line = null, $offset = null, $level = null)
    {

        $this->line = $line ?: 0;
        $this->offset = $offset ?: 0;
        $this->level = $level ?: 0;
    }
}