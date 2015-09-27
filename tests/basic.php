<?php

include '../vendor/autoload.php';

$lexer = new Tale\Jade\Parser();

var_dump($lexer->lex(file_get_contents('views/layout-basic.jade')));