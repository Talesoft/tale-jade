<?php

namespace Tale\Jade;

/**
 * @param array|null $options
 * @param Compiler|null $compiler
 * @param Lexer|null $lexer
 * @return Renderer
 */
function create_renderer(array $options = null, Compiler $compiler = null, Lexer $lexer = null)
{

    return new Renderer($options, $compiler, $lexer);
}

/**
 * @param $file
 * @param array|null $args
 * @param array|null $options
 * @return mixed
 */
function render($file, array $args = null, array $options = null)
{

    $renderer = create_renderer($options);
    return $renderer->render($file, $args);
}
