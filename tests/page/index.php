<?php

include '../../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_reporting', E_ALL | E_NOTICE);

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;


$minify = isset($_GET['minify']) ? true : false;
$handleErrors = isset($_GET['withErrorHandler']) ? true : false;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {

    $compiler = new Compiler([
        'pretty' => !$minify,
        'handleErrors' => $handleErrors,
        'allowImports' => false
    ]);

    $jade = isset($_POST['jade']) ? $_POST['jade'] : '';

    header('Content-Type: text/plain; encoding=utf-8');

    try {
        echo json_encode($compiler->compile(str_replace("\t", '    ', $jade)));
    } catch(Exception $e) {

        echo json_encode($e->getMessage());
    }
    exit;
}


$renderer = new Renderer([
    'compiler' => [
        'pretty' => true
    ],
    'adapter' => 'file',
    'adapterOptions'=> [
        'lifeTime' => 0
    ]
]);


$example = isset($_GET['example']) ? $_GET['example'] : 'welcome';
$exampleJade = '';


$example = preg_replace('/[^a-z0-9\-]+/i', '', $example);
if (file_exists(__DIR__.'/examples/'.$example.'.jade'))
    $exampleJade = file_get_contents(__DIR__.'/examples/'.$example.'.jade');

$action = isset($_GET['page']) ? preg_replace('/^[a-z0-9\-]+$/i', '', $_GET['example']) : 'index';
$exampleJade = json_encode($exampleJade);
$url = $_SERVER['PHP_SELF'];


header('Content-Type: text/html; encoding=utf-8');
mb_internal_encoding('UTF-8');
ob_start('mb_output_handler');

echo $renderer->render('views/index', [
    'action' => $action,
    'example' => $example,
    'exampleJade' => $exampleJade,
    'url' => $url,
    'minify' => json_encode($minify),
    'handleErrors' => json_encode($handleErrors)
]);

