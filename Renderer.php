<?php
/**
 * The Tale Jade Project
 *
 * The Renderer Class
 *
 * This file is part of the Tale Jade Template Engine for PHP
 *
 * @author Torben Köhn <tk@talesoft.io>
 * @author Talesoft <info@talesoft.io>
 * @projectGroup Tale
 * @project Jade
 * @component Renderer
 *
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * Please do not remove this comment block.
 * Thank you and have fun with Tale Jade!
 */

namespace Tale\Jade;

/**
 * Allows easy rendering of Jade-files
 *
 * The renderer provides utilities to quickly render jade files to
 * HTML-output or/and to files
 *
 * @package Tale\Jade
 */
class Renderer
{

    /**
     * The options-array for the renderer instance
     *
     * @var array
     */
    private $_options;

    /**
     * The compiler that is used in this renderer instance
     * @var \Tale\Jade\Compiler
     */
    private $_compiler;

    /**
     * The parser that is used in this renderer instance
     * @var \Tale\Jade\Parser
     */
    private $_parser;

    /**
     * The lexer that is used in this renderer instance
     * @var \Tale\Jade\Lexer
     */
    private $_lexer;

    /**
     * The adapter that actually renders the files in a dynamic manner
     * @var \Tale\Jade\Renderer\AdapterBase
     */
    private $_adapter;

    /**
     * Creates a new Tale Jade Renderer instance to render Jade files
     *
     * Use the ->render() method on the resulting object to render
     * your jade files
     *
     * Possible options are:
     * adapter: The name of the adapter to use, either a short-name
     *          for an internal adapter or a class-name for a custom
     *          adapter
     * adapterOptions: The option-array that gets passed to the adapter
     * compiler: The compiler-options that get passed to the compiler
     * parser: The parser-options that get passed to the parser
     * lexer: The lexer options that get passed to the lexer
     *
     * @param array|null               $options  The options to pass to the renderer
     * @param \Tale\Jade\Compiler|null $compiler The compiler to use inside the renderer
     * @param \Tale\Jade\Parser|null   $parser   The parser to use inside the compiler
     * @param \Tale\Jade\Lexer|null    $lexer    The lexer to use inside the parser
     */
    public function __construct(
        array $options = null,
        Compiler $compiler = null,
        Parser $parser = null,
        Lexer $lexer = null
    )
    {

        $this->_options = array_replace_recursive([
            'adapter'        => 'stream',
            'adapterOptions' => [],
            'compiler'       => [],
            'parser'         => [],
            'lexer'          => []
        ], $options ? $options : []);

        $this->_lexer = $lexer ? $lexer : new Lexer($this->_options['lexer']);
        $this->_parser = $parser ? $parser : new Parser($this->_options['parser'], $lexer);
        $this->_compiler = $compiler ? $compiler : new Compiler($this->_options['compiler'], $parser);
    }

    /**
     * Returns the current option-array used in this
     * renderer instance
     *
     * @return array
     */
    public function getOptions()
    {

        return $this->_options;
    }

    /**
     * Return the compiler instance used in this
     * renderer instance
     * @return \Tale\Jade\Compiler
     */
    public function getCompiler()
    {

        return $this->_compiler;
    }

    /**
     * Returns the parser instance used in this
     * renderer instance
     * @return \Tale\Jade\Parser
     */
    public function getParser()
    {

        return $this->_parser;
    }

    /**
     * Returns the lexer used in this
     * renderer instance
     * @return \Tale\Jade\Lexer
     */
    public function getLexer()
    {

        return $this->_lexer;
    }

    /**
     * Returns the adapter that actually renders the files
     *
     * This is lazy, meaning that the adapter gets created and stored as soon
     * as the method is called the first time.
     * After that all calls will return the same adapter instance
     *
     * @return \Tale\Jade\Renderer\AdapterBase
     */
    public function getAdapter()
    {

        if (!isset($this->_adapter)) {

            $adapter = $this->_options['adapter'];
            $className = strpos($adapter, '\\') === false
                ? __NAMESPACE__.'\\Renderer\\Adapter\\'.ucfirst($this->_options['adapter'])
                : $adapter;

            if (!class_exists($className))
                throw new \RuntimeException(
                    "The passed adapter doesnt exist"
                );

            if (!is_subclass_of($className, __NAMESPACE__.'\\Renderer\\AdapterBase'))
                throw new \RuntimeException(
                    "The passed adapter doesnt extend Tale\\Jade\\Renderer\\AdapterBase"
                );

            $this->_adapter = new $className($this, $this->_options['adapterOptions']);
        }

        return $this->_adapter;
    }

    /**
     * Compiles a Jade-string to PHTML
     * The result can then be evaluated, the best method is
     * a simple PHP include
     *
     * Use ->render() to get this done for you
     *
     * Before evaluating you should set a $__args variable
     * that will be passed through mixins.
     * It like a global scope.
     *
     * If you give it a path, the directory of that path will be used
     * for relative includes.
     *
     * @param string      $input The jade input string
     * @param string|null $path  The path for relative includes
     *
     * @return mixed|string A PHTML string containing HTML and PHP
     */
    public function compile($input, $path = null)
    {

        return $this->_compiler->compile($input, $path);
    }

    /**
     * Compiles a file to PHTML
     *
     * The given path will automatically passed as
     * compile()'s $path argument
     *
     * The path should always be relative to the paths-option paths
     *
     * @see \Tale\Jade\Renderer->compile()
     *
     * @param string $path The path to the jade file
     *
     * @return mixed|string The compiled PHTML
     * @throws \Exception
     */
    public function compileFile($path)
    {

        return $this->_compiler->compileFile($path);
    }

    /**
     * Compiles and includes a Jade-file correctly so that
     * you directly get the desired HTML output
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
     * @param string     $file The relative path to the file to render
     * @param array|null $args An array of variables to pass to the Jade-file
     *
     * @return string The renderered markup
     */
    public function render($file, array $args = null)
    {

        return $this->getAdapter()->render($file, $args);
    }
}