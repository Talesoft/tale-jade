<?php

namespace Tale\Jade\Lexer;

interface TokenInterface
{

    public function getLine();
    public function getOffset();
    public function getLevel();
    public function __toString();
}