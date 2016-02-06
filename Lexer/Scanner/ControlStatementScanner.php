<?php

namespace Tale\Jade\Lexer\Scanner;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\ScannerInterface;

class ControlStatementScanner implements ScannerInterface
{

    private $_tokenClassName;
    private $_names;

    public function __construct($tokenClassName, array $names)
    {

        $this->_tokenClassName = $tokenClassName;
        $this->_names = $names;
    }

    public function scan(Lexer $lexer)
    {

        $reader = $lexer->getReader();
        $names = implode('|', $this->_names);

        if (!$reader->match("({$names})[ \t]+"))
            return;

        $token = $lexer->createToken($this->_tokenClassName);
        $name = $reader->getMatch(1);
        $reader->consume();

        if (method_exists($token, 'setName'))
            $token->setName($name);

        if (method_exists($token, 'setSubject'))
            $token->setSubject($reader->readExpression(["\n", ":"]));

        yield $token;

        foreach ($lexer->scan(SubScanner::class) as $token)
            yield $token;
    }
}