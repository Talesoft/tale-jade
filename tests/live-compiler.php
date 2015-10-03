<?php

include '../vendor/autoload.php';

use Tale\Jade\Renderer;
use Tale\Jade\Compiler;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {

    $compiler = new Compiler([
        'pretty' => true,
        'handleErrors' => false
    ]);

    $jade = isset($_POST['jade']) ? $_POST['jade'] : '';

    header('Content-Type: text/plain; encoding=utf-8');

    try {
        echo $compiler->compile(str_replace("\t", '    ', $jade));
    } catch(Exception $e) {

        echo $e->getMessage();
    }
    exit;
}


$renderer = new Renderer([
    'compiler' => [
        'pretty' => false
    ]
]);

echo $renderer->render('live-compiler/index');