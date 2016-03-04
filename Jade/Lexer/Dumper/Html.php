<?php

namespace Tale\Jade\Lexer\Dumper;

use Tale\Jade\Lexer\TokenInterface;

class Html extends Text
{
    protected function getPrefix()
    {

        return '<pre>';
    }

    protected function getSuffix()
    {

        return '</pre>';
    }

    protected function dumpToken(TokenInterface $token)
    {

        $name = $this->getTokenName($token);
        $html = '';
        switch (get_class($token)) {
            default:

                $html = parent::dumpToken($token);
        }

        return $html;
    }
}