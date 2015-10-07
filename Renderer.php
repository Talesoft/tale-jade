<?php

namespace Tale\Jade;

/**
 * Class Renderer
 * @package Tale\Jade
 */
class Renderer
{

    /**
     * @var array
     */
    private $_options;
    /**
     * @var Compiler
     */
    private $_compiler;
    /**
     * @var Parser
     */
    private $_parser;
    /**
     * @var Lexer
     */
    private $_lexer;
    /**
     * @var
     */
    private $_adapter;

    /**
     * @param array|null $options
     * @param Compiler|null $compiler
     * @param Parser|null $parser
     * @param Lexer|null $lexer
     */
    public function __construct(
        array $options = null,
        Compiler $compiler = null,
        Parser $parser = null,
        Lexer $lexer = null
    )
    {

        $this->_options = array_replace_recursive([
            'adapter' => 'stream',
            'adapterOptions' => [],
            'compiler' => [],
            'parser' => [],
            'lexer' => []
        ], $options ? $options : []);

        $this->_lexer = $lexer ? $lexer : new Lexer($this->_options['lexer']);
        $this->_parser = $parser ? $parser : new Parser($this->_options['parser'], $lexer);
        $this->_compiler = $compiler ? $compiler : new Compiler($this->_options['compiler'], $parser);
    }

    /**
     * @return array
     */
    public function getOptions()
    {

        return $this->_options;
    }

    /**
     * @return \Tale\Jade\Compiler
     */
    public function getCompiler()
    {

        return $this->_compiler;
    }

    /**
     * @return \Tale\Jade\Parser
     */
    public function getParser()
    {

        return $this->_parser;
    }

    /**
     * @return \Tale\Jade\Lexer
     */
    public function getLexer()
    {

        return $this->_lexer;
    }

    /**
     * @return mixed
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
     * @param $input
     * @param null $path
     * @return mixed|string
     */
    public function compile($input, $path = null)
    {

        return $this->_compiler->compile($input, $path);
    }

    /**
     * @param $path
     * @return mixed|string
     * @throws \Exception
     */
    public function compileFile($path)
    {

        return $this->_compiler->compileFile($path);
    }

    /**
     * @param $file
     * @param array|null $args
     * @return mixed
     */
    public function render($file, array $args = null)
    {

        return $this->getAdapter()->render($file, $args);
    }
}