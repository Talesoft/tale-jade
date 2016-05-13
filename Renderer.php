<?php
/**
 * The Tale Jade Renderer.
 *
 * Contains the renderer that compiles Jade for you
 * and renders it through different, configurable adapters
 *
 * This file is part of the Tale Jade Template Engine for PHP
 *
 * LICENSE:
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * @category   Presentation
 * @package    Tale\Jade
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/files/Renderer.html
 * @since      File available since Release 1.0
 */

namespace Tale\Jade;

use Tale\ConfigurableTrait;
use Tale\Jade\Renderer\AdapterBase;

/**
 * Allows easy rendering of Jade-files to markup.
 *
 * The renderer provides utilities to quickly render jade files to
 * HTML/XML-output or/and to files
 *
 * Usage example:
 * <code>
 *
 *     use Tale\Jade\Renderer;
 *
 *     $renderer = new Renderer();
 *
 *     echo $renderer->render('index');
 *     //Where "index" is a "index.jade" jade source file
 *
 * </code>
 *
 * @category   Presentation
 * @package    Tale\Jade
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/classes/Tale.Jade.Renderer.html
 * @since      File available since Release 1.0
 */
class Renderer
{
    use ConfigurableTrait;

    /**
     * The compiler that is used in this renderer instance.
     *
     * @var Compiler
     */
    private $compiler;

    /**
     * The parser that is used in this renderer instance.
     *
     * @var Parser
     */
    private $parser;

    /**
     * The lexer that is used in this renderer instance.
     *
     * @var Lexer
     */
    private $lexer;

    /**
     * The adapter that actually renders the files in a dynamic manner.
     *
     * @var AdapterBase
     */
    private $adapter;

    /**
     * Creates a new Tale Jade Renderer instance to render Jade files.
     *
     * Use the ->render() method on the resulting object to render
     * your jade files
     *
     * Possible options are:
     *
     * adapter:         The name of the adapter to use, either a short-name
     *                  for an internal adapter or a class-name for a custom
     *                  adapter
     * adapter_options:  The option-array that gets passed to the adapter
     * compiler:        The compiler-options that get passed to the compiler
     * parser_options:   The parser-options that get passed to the parser
     * lexer_options:    The lexer options that get passed to the lexer
     *
     * pretty:          Compile with indentations and newlines (default: false)
     * paths:           The paths the compiler should search the jade files in
     *
     * @param array|null    $options  the options to pass to the renderer
     * @param Compiler|null $compiler the compiler to use inside the renderer
     * @param Parser|null   $parser   the parser to use inside the compiler
     * @param Lexer|null    $lexer    the lexer to use inside the parser
     */
    public function __construct(
        array $options = null,
        Compiler $compiler = null,
        Parser $parser = null,
        Lexer $lexer = null
    )
    {

        $this->defineOptions([
            'adapter'           => 'file',
            'adapter_options'    => [],
            'compiler_options'   => [],
            'parser_options'     => [],
            'lexer_options'      => [],
        ], $options);

        //Quick Options.
        //These get passed to the actual option arrays of the related objects
        //@DEPRECATED lifeTime and cachePath will be removed in the near future!
        $this->forwardOption('lifeTime', 'adapter_options', 'ttl');
        $this->forwardOption('ttl', 'adapter_options');
        $this->forwardOption('cachePath', 'adapter_options', 'path');
        $this->forwardOption('cache_dir', 'adapter_options', 'path');
        $this->forwardOption('cache_path', 'adapter_options', 'path');

        $this->forwardOption('paths', 'compiler_options');
        $this->forwardOption('pretty', 'compiler_options');
        $this->forwardOption('indent_style', 'compiler_options');
        $this->forwardOption('indent_width', 'compiler_options');
        $this->forwardOption('stand_alone', 'compiler_options');
        $this->forwardOption('extensions', 'compiler_options');
        $this->forwardOption('mode', 'compiler_options');
        $this->forwardOption('doctypes', 'compiler_options');
        $this->forwardOption('filters', 'compiler_options');
        $this->forwardOption('filter_map', 'compiler_options');

        $this->lexer = $lexer ?: new Lexer($this->options['lexer_options']);
        $this->parser = $parser ?: new Parser($this->options['parser_options'], $this->lexer);
        $this->compiler = $compiler ?: new Compiler($this->options['compiler_options'], $this->parser);
    }

    /**
     * Return the compiler instance used in this renderer instance.
     *
     * @return Compiler
     */
    public function getCompiler()
    {

        return $this->compiler;
    }

    /**
     * Returns the parser instance used in this renderer instance.
     *
     * @return Parser
     */
    public function getParser()
    {

        return $this->parser;
    }

    /**
     * Returns the lexer used in this renderer instance.
     *
     * @return Lexer
     */
    public function getLexer()
    {

        return $this->lexer;
    }

    /**
     * Adds a path to the compiler to search files in.
     *
     * This is just a proxy for the addPath-method of the Compiler
     *
     * @see Compiler->addPath
     *
     * @param string $path the path to add
     *
     * @return $this
     */
    public function addPath($path)
    {

        $this->getCompiler()->addPath($path);

        return $this;
    }

    /**
     * Adds a new filter to the compiler.
     *
     * The filter can be called inside jade via the :<filterName>-syntax
     *
     * The signature of the callback should be
     * (Node $node, $indent, $newLine)
     * where $node is the filter node that was encountered (including its children)
     * and $indent and $newLine are indentation and the new line character
     * as a string respecting the compiler's 'pretty' option
     *
     * This is just a proxy for the Compiler's addFilter method
     *
     * @see Compiler->addFilter
     *
     * @param string $name
     * @param callable $callback
     *
     * @return $this
     */
    public function addFilter($name, $callback)
    {

        $this->getCompiler()->addFilter($name, $callback);

        return $this;
    }

    /**
     * Returns the adapter that actually renders the files.
     *
     * This is lazy, meaning that the adapter gets created and stored as soon
     * as the method is called the first time.
     * After that all calls will return the same adapter instance
     *
     * @return AdapterBase
     */
    public function getAdapter()
    {

        if (!isset($this->adapter)) {

            $adapter = $this->options['adapter'];
            $className = strpos($adapter, '\\') === false
                ? __NAMESPACE__.'\\Renderer\\Adapter\\'.ucfirst($this->options['adapter'])
                : $adapter;

            if (!class_exists($className))
                throw new \RuntimeException(
                    "The passed adapter doesnt exist"
                );

            if (!is_subclass_of($className, __NAMESPACE__.'\\Renderer\\AdapterBase'))
                throw new \RuntimeException(
                    "The passed adapter doesnt extend Tale\\Jade\\Renderer\\AdapterBase"
                );

            $this->adapter = new $className($this, $this->options['adapter_options']);
        }

        return $this->adapter;
    }

    /**
     * Compiles a Jade-string to PHTML.
     *
     * The result can then be evaluated, the best method is
     * a simple PHP include
     *
     * Use ->render() to get this done for you
     *
     * Before evaluating you should set a $__args variables
     * that will be passed through mixins.
     * It like a global scope.
     *
     * If you give it a path, the directory of that path will be used
     * for relative includes.
     *
     * @param string      $input the jade input string
     * @param string|null $path  the path for relative includes
     *
     * @return mixed|string A PHTML string containing HTML and PHP
     */
    public function compile($input, $path = null)
    {

        return $this->compiler->compile($input, $path);
    }

    /**
     * Compiles a file to PHTML.
     *
     * The given path will automatically passed as
     * compile()'s $path argument
     *
     * The path should always be relative to the paths-option paths
     *
     * @see Renderer->compile
     *
     * @param string $path The path to the jade file
     *
     * @return mixed|string the compiled PHTML
     * @throws \Exception when the file wasnt found or the compilation,
     *                    lexing or parsing failed
     */
    public function compileFile($path)
    {

        return $this->compiler->compileFile($path);
    }

    /**
     * Renders a jade-file to a markup-string directly.
     *
     * This is the essence of the Jade-renderer and is
     * the shortest and easiest way to get Jade running
     * in your project
     *
     * Notice that if your file wasn't found, you need to
     * pass _relative_ paths.
     *
     * The paths will be relative from the compiler:paths option
     * or from get_include_path(), if no paths have been defined
     *
     * @param string     $file the relative path to the file to render
     * @param array|null $args an array of variables to pass to the Jade-file
     *
     * @return string The renderered markup
     */
    public function render($file, array $args = null)
    {

        return $this->getAdapter()->render($file, $args);
    }
}