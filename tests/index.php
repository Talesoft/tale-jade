<?php

include '../vendor/autoload.php';

$lexer = new Tale\Jade\Lexer();

ini_set('xdebug.var_display_max_depth', 10);

var_dump('L1', $lexer->lex(file_get_contents('views/index.jade')));
var_dump('L2', $lexer->lex(file_get_contents('views/layout-basic.jade')));