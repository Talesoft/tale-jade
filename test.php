<?php

use Tale\Jade\Compiler;


include 'vendor/autoload.php';

$compiler = new Compiler();


var_dump($compiler->compile('| !{$someExpression->{$someProperty}} #[p Some #{$x["y"]} item!]  #[a Some link]'));