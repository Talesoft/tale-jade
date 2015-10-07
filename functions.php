<?php

namespace Tale\Jade;

function create_renderer(array $options = null, Compiler $compiler = null, Lexer $lexer = null)
{

    return new Renderer($options, $compiler, $lexer);
}

function render($file, array $args = null, array $options = null)
{

    $renderer = create_renderer($options);
    return $renderer->render($file, $args);
}
