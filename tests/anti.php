<?php

include '../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_reporting', E_ALL | E_NOTICE);

$compiler = new Tale\Jade\Compiler();

$tests = glob(__DIR__.'/anti/*.jade');

foreach ($tests as $path) {

    echo '<strong>Testing '.basename($path, '.jade').'</strong><br>';

    try {

        $jade = $compiler->compileFile($path);

        echo '<strong style="color: red">Failed</strong><br>';
    } catch(Exception $e) {

        echo '<strong style="color: green">Passed</strong><br>';
    }

    echo '<br>';
}