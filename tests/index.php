<?php

include '../vendor/autoload.php';

$lexer = new Tale\Jade\Lexer();

ini_set('xdebug.var_display_max_depth', 10);

$lexer->dump(file_get_contents('views/index.jade'));
$lexer->dump(file_get_contents('views/layout-basic.jade'));