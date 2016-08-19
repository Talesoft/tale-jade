<?php
/**
 * The Tale Jade Helper Functions.
 *
 * Contains a bunch of helper-functions that ease up
 * development in a procedural or party-procedural environment
 *
 * This file is part of the Tale Jade Template Engine for PHP
 *
 * LICENSE:
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here https://github.com/Talesoft/tale-jade/blob/master/LICENSE.md
 *
 * @category   Presentation
 * @package    Tale\Jade
 * @author     Torben Koehn <torben@talesoft.codes>
 * @author     Talesoft <info@talesoft.codes>
 * @copyright  Copyright (c) 2015-2016 Torben Köhn (http://talesoft.codes)
 * @license    https://github.com/Talesoft/tale-jade/blob/master/LICENSE.md MIT License
 * @version    1.4.5
 * @link       http://jade.talesoft.codes/docs/files/functions.html
 * @since      File available since Tag 1.0.1
 */

namespace Tale\Jade;

/**
 * Creates a new Tale Jade Renderer instance to render Jade files.
 *
 * Use the ->render() method on the resulting object to render
 * your jade files
 *
 * @param array|null    $options  the options to pass to the renderer
 * @param Compiler|null $compiler the compiler to use inside the renderer
 * @param Parser|null   $parser   the parser to use inside the compiler
 * @param Lexer|null    $lexer    the lexer to use inside the parser
 *
 * @return Renderer the newly created renderer
 */
function create_renderer(array $options = null, Compiler $compiler = null, Parser $parser = null, Lexer $lexer = null)
{

    return new Renderer($options, $compiler, $parser, $lexer);
}

/**
 * Renders a file through a renderer that is created on-the-fly.
 *
 * To specify a directory to render from, use the 'paths'-option
 * of the compiler like this:
 *
 * <code>
 *
 *      render('some-file', [<args>], [
 *          'paths' => [__DIR__.'/path/to/views']
 *      ]);
 *
 * @param string     $file    the relative file path to compile
 * @param array|null $args    the variables to pass to the template
 * @param array|null $options the options to pass to the renderer
 *
 * @return string the rendered markup
 */
function render($file, array $args = null, array $options = null)
{

    $renderer = create_renderer($options);

    return $renderer->render($file, $args);
}