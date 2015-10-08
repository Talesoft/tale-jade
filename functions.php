<?php

namespace Tale\Jade;

/**
 * Creates a new Tale Jade Renderer instance to render Jade files
 *
 * Use the ->render() method on the resulting object to render
 * your jade files
 *
 * @param array|null $options The options to pass to the renderer
 * @param \Tale\Jade\Compiler|null $compiler The compiler to use inside the renderer
 * @param \Tale\Jade\Parser|null $parser The parser to use inside the compiler
 * @param \Tale\Jade\Lexer|null $lexer The lexer to use inside the parser
 * @return \Tale\Jade\Renderer Thew newly created renderer
 */
function create_renderer(array $options = null, Compiler $compiler = null, Parser $parser = null, Lexer $lexer = null)
{

    return new Renderer($options, $compiler, $parser, $lexer);
}

/**
 * Renders a file through a renderer that is created
 * on-the-fly.
 *
 * @param string $file The relative file path to compile
 * @param array|null $args The variables to pass to the template
 * @param array|null $options The options to pass to the renderer
 * @return string The rendered markup
 */
function render($file, array $args = null, array $options = null)
{

    $renderer = create_renderer($options);
    return $renderer->render($file, $args);
}
