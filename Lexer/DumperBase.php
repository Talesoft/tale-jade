<?php

namespace Tale\Jade\Lexer;

abstract class DumperBase implements DumperInterface
{

    public function getTokenName(TokenInterface $token)
    {

        return basename(get_class($token), 'Token');
    }

    protected function getPrefix()
    {

        return '';
    }

    protected function getSuffix()
    {

        return '';
    }

    abstract protected function dumpToken(TokenInterface $token);

    public function dump(\Generator $tokens)
    {

        $string = $this->getPrefix();
        foreach ($tokens as $token)
            $string .= $this->dumpToken($token);
        $string .= $this->getSuffix();

        return $string;
    }
}