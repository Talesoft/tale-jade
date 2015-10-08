<?php
/**
 * The Tale Jade Project
 *
 * The Compiler Class
 *
 * This file is part of the Tale Jade Template Engine for PHP
 *
 * @author Torben Köhn <tk@talesoft.io>
 * @author Talesoft <info@talesoft.io>
 * @projectGroup Tale
 * @project Jade
 * @component Compiler
 *
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * Please do not remove this comment block.
 * Thank you and have fun with Tale Jade!
 */

namespace Tale\Jade;

use Tale\Jade\Compiler\Exception;
use Tale\Jade\Parser\Node;

/**
 * Compiles the parsed AST into PHTML
 *
 * Translates Nodes returned from the Parser into PHTML, a mix
 * of PHP and HTML
 *
 * The main entry point is the `compile` method
 * Compilation looks like this
 *
 * $compiler->compile($inputString)
 *
 * The generated PHTML should be evaluated, the best method
 * is a simple include of a generated file
 *
 * @package Tale\Jade
 */
class Compiler
{

    /**
     * The Mode for HTML
     * Recognizes self-closing tags, self-repeating attributes etc.
     */
    const MODE_HTML = 0;

    /**
     * The Mode of XML
     * Doesn't do whatever MODE_HTML does
     */
    const MODE_XML = 1;

    /**
     * An array of options
     * @var array
     */
    private $_options;

    /**
     * The lexer that is given to the parser
     * @var Lexer
     */
    private $_lexer;

    /**
     * The parse this compiler instance gets its nodes off
     * @var Parser
     */
    private $_parser;

    /**
     * The current file stack.
     * The bottom file is the file that is currently compiled.
     * This is needed for recursive path resolving in imports
     * @var string[]
     */
    private $_files;

    /**
     * The mixins we found in the whole input
     * We use this to check if a mixin exists upon call
     * and to compile them all at the and (with checking,
     * if they are even called)
     *
     * The array looks like this:
     * [
     *     ['node' => \Tale\Jade\Parser\Node, 'phtml' => <compiled phtml> ],
     *     ['node' => \Tale\Jade\Parser\Node, 'phtml' => <compiled phtml> ],
     *     ['node' => \Tale\Jade\Parser\Node, 'phtml' => <compiled phtml> ]
     * ]
     *
     * Keys are the name of the mixin
     * @var []
     */
    private $_mixins;

    /**
     * A stack of names of the mixins we actually called in the code
     * @var string[]
     */
    private $_calledMixins;

    /**
     * A list of all blocks in our whole input
     * They are only used in handleBlocks and handleBlock
     * @var \Tale\Jade\Parser\Node[]
     */
    private $_blocks;

    /**
     * The level we're currently in.
     * This doesn't equal the current level in the parser or lexer,
     * it rather represents the current indentation level
     * for pretty compiling
     * @var int
     */
    private $_level;

    /**
     * Creates a new compiler instance
     *
     * You can pass a modified parser or lexer.
     * Notice that if you pass both, the lexer inside the parser will be used.
     *
     * Valid options are:
     *      pretty: Use indentation and new-lines or compile everything into a single line
     *      indentStyle: The character that is used for indentation (Space by default)
     *      indentWidth: The amount of characters to repeat for indentation (Default 2 for 2-space-indentation)
     *      mode: Compile in HTML or XML mode
     *      selfClosingTags: The tags that don't need any closing in HTML-style languages
     *      selfRepeatingAttributes: The attributes that repeat their value to set them to true in HTML-style languages
     *      doctypes: The different doctypes you can use via the "doctype"-directive [name => doctype-string]
     *      filters: The different filters you can use via the ":<filterName>"-directive [name => callback]
     *      filterMap: The extension-to-filter-map for include-filters [extension => filter]
     *      escapeSequences: The escape-sequences that are possible in scalar strings
     *      handleErrors: Should the error-handler-helper be appended to the PTHML or not
     *      compileUncalledMixins: Always compile all mixins or leave out those that aren't called?
     *      allowImports: Set to false to disable imports for this compiler instance. Importing will throw an exception.
     *                    Great for demo-pages
     *      defaultTag: The tag to default to for class/id/attribute-initiated elements (.abc, #abc, (abc))
     *      quoteStyle: The quote-style in the markup (default: ")
     *      replaceMixins: Replaces mixins from top to bottom if they have the same name. Allows duplicated mixin names.
     *      paths: The paths to resolve paths in. If none set, it will default to get_include_path()
     *      extension: The extension for Jade files (default: .jade), .jd is pretty cool as an example
     *      parser: The options for the parser if none given
     *      lexer: The options for the lexer if none given.
     *
     *
     * @param array|null  $options An array of options
     * @param Parser|null $parser  An existing parser instance
     * @param Lexer|null  $lexer   An existing lexer instance
     */
    public function __construct(array $options = null, Parser $parser = null, Lexer $lexer = null)
    {

        $this->_options = array_replace_recursive([
            'pretty'                  => false,
            'indentStyle'             => Lexer::INDENT_SPACE,
            'indentWidth'             => 2,
            'mode'                    => self::MODE_HTML,
            'selfClosingTags'         => [
                'input', 'br', 'img', 'link'
            ],
            'selfRepeatingAttributes' => [
                'selected', 'checked', 'disabled'
            ],
            'doctypes'                => [
                '5'            => '<!DOCTYPE html>',
                'xml'          => '<?xml version="1.0" encoding="utf-8"?>',
                'default'      => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
                'transitional' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
                'strict'       => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
                'frameset'     => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
                '1.1'          => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
                'basic'        => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">',
                'mobile'       => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">'
            ],
            'filters'                 => [
                'plain' => 'Tale\\Jade\\Filter::filterPlain',
                'css'   => 'Tale\\Jade\\Filter::filterStyle',
                'js'    => 'Tale\\Jade\\Filter::filterScript',
                'php'   => 'Tale\\Jade\\Filter::filterCode',
                //'markdown' => 'Tale\\Jade\\Filter::filterMarkdown'
                //What else?
            ],
            'filterMap'               => [
                'css'  => 'css',
                'js'   => 'js',
                'php'  => 'php',
                'md'   => 'markdown',
                'jade' => 'plain'
            ],
            'escapeSequences'         => [
                '\n' => "\n",
                '\r' => "\r",
                '\t' => "\t"
            ],
            'handleErrors'            => true,
            'compileUncalledMixins'   => false,
            'allowImports'            => true,
            'defaultTag'              => 'div',
            'quoteStyle'              => '"',
            'replaceMixins'           => false,
            'paths'                   => [],
            'extension'               => '.jade',
            'parser'                  => [],
            'lexer'                   => []
        ], $options ? $options : []);

        $this->_lexer = $lexer ? $lexer : new Lexer($this->_options['lexer']);
        $this->_parser = $parser ? $parser : new Parser($this->_options['parser'], $this->_lexer);
    }

    /**
     * Returns the current options for the parser
     * @return array
     */
    public function getOptions()
    {

        return $this->_options;
    }

    /**
     * Returns the current lexer used
     * @return \Tale\Jade\Lexer
     */
    public function getLexer()
    {

        return $this->_lexer;
    }

    /**
     * Returns the current parser used
     * @return \Tale\Jade\Parser
     */
    public function getParser()
    {

        return $this->_parser;
    }

    /**
     * Adds a path to the compiler
     * Files will be loaded from this path (or other paths you added before)
     *
     * @param string $path The directory path
     *
     * @return $this
     */
    public function addPath($path)
    {

        $this->_options['paths'][] = $path;

        return $this;
    }

    /**
     * Adds a filter to the compiler
     * This filter can then be used inside jade with the
     * :<filtername> directive
     *
     * The callback should have the following signature:
     * (\Tale\Jade\Parser\Node $node, $indent, $newLine)
     * where $node is the filter \Tale\Jade\Parser\Node found,
     * $indent is the current indentation respecting level and pretty-option
     * and newLine is a new-line respecting the pretty-option
     *
     * It should return either a PHTML string or a \Tale\Jade\Parser\Node-instance
     *
     * @param string   $name     The name of the filter
     * @param callable $callback The filter handler callback
     *
     * @return $this
     */
    public function addFilter($name, $callback)
    {

        if (!is_callable($callback))
            throw new \InvalidArgumentException(
                "Argument 2 of addFilter must be valid callback"
            );

        $this->_options['filters'][$name] = $callback;

        return $this;
    }

    /**
     * Compiles a Jade-string to PHTML
     * The result can then be evaluated, the best method is
     * a simple PHP include
     *
     * Look at \Tale\Jade\Renderer to get this done for you
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

        //Compiler reset
        $this->_files = $path ? [$path] : [];
        $this->_mixins = [];
        $this->_calledMixins = [];
        $this->_blocks = [];
        $this->_level = 0;

        //Parse the input into an AST
        $node = $this->_parser->parse($input);

        //There are some things we need to take care of before compilation
        $this->handleImports($node);
        $this->handleBlocks($node);
        $this->handleMixins($node);

        //The actual compilation process ($node is the very root \Tale\Jade\Parser\Node of everything)
        $phtml = $this->compileNode($node);


        //Reset the level again for our next operations
        $this->_level = 0;
        //Now we append/prepend specific stuff (like mixin functions and helpers)
        $errorHandler = $this->compileErrorHandlerHelper();
        $mixins = $this->compileMixins();


        //Put everything together
        $phtml = implode('', [$errorHandler, $mixins, $phtml]);

        if ($this->_options['handleErrors'])
            $phtml .= $this->createCode('restore_error_handler(); unset($__errorHandler);');

        //Reset the files after compilation so that compileFile may resolve correctly
        //Happens when you call compileFile twice on different files
        //Note that Compiler only uses the include-path, when there is no file in the
        //file name storage $_files
        $this->_files = [];

        //Return the compiled PHTML
        return $phtml;
    }

    /**
     * Compiles a file to PHTML
     *
     * The given path will automatically passed as
     * compile()'s $path argument
     *
     * The path should always be relative to the paths-option paths
     *
     * @see \Tale\Jade\Compiler->compile()
     *
     * @param string $path The path to the jade file
     *
     * @return mixed|string The compiled PHTML
     * @throws \Exception
     */
    public function compileFile($path)
    {

        $fullPath = $this->resolvePath($path);

        if (!$fullPath)
            throw new \Exception(
                "File $path wasnt found in ".implode(', ', $this->_options['paths']).", Include path: ".get_include_path()
            );

        return $this->compile(file_get_contents($fullPath), $fullPath);
    }

    /**
     * Checks if a variable is scalar (or "not an expression")
     * These values don't get much special handling, they are mostly
     * simple attributes values like `type="button"` or `method='post'`
     *
     * A scalar value is either a closed string containing only
     * a-z, A-Z, 0-9, _ and -, e.g. Some-Static_Value
     * or a quote-enclosed string that can contain anything
     * except the quote style it used
     * e.g. "Some Random String", 'This can" contain quotes"'
     *
     * @param string $value The value to be checked
     *
     * @return bool
     */
    protected function isScalar($value)
    {

        return preg_match('/^([a-z0-9\_\-]+|"[^"]*"|\'[^\']*\')$/i', $value) ? true : false;
    }


    /**
     * Compiles and sanitizes a scalar value
     *
     * @param string     $value     The scalar value
     * @param bool|false $attribute Is this an attribute value or not
     *
     * @return string
     */
    protected function compileScalar($value, $attribute = false)
    {

        $sequences = $this->_options['escapeSequences'];

        return $this->interpolate(trim(str_replace(array_keys($sequences), $sequences, $value), '\'"'), $attribute);
    }

    /**
     * Checks if a value is a variable
     * A variable needs to start with $.
     * After that only a-z, A-Z and _ can follow
     * After that you can use any character of
     * a-z, A-Z, 0-9, _, [, ], -, >, ' and "
     * This will match all of the following:
     *
     * $__someVar
     * $obj->someProperty
     * $arr['someKey']
     * $arr[0]
     * $obj->someArray['someKey']
     * etc.
     *
     * @param string $value The value to be checked
     *
     * @return bool
     */
    protected function isVariable($value)
    {

        return preg_match('/^\$[a-z_][a-z0-9\_\[\]\->\'"]*$/i', $value) ? true : false;
    }

    /**
     * Interpolates a string value
     * Interpolation is initialized with # (escaped) or ! (not escaped)
     *
     * After that use either {} brackets for variable expressions
     * or [] for Jade-expressions
     *
     * e.g.
     *
     * #{$someVariable}
     * !{$someObj->someProperty}
     *
     * #[p This is some paragraph]
     *
     * If the second paragraph is true, the result will act like it is
     * inside a string respecting the quoteStyle-option
     *
     * @param string     $string    The string to interpolate
     * @param bool|false $attribute Is this an attribute value or not
     *
     * @return string The interpolated PHTML
     */
    protected function interpolate($string, $attribute = false)
    {

        $string = preg_replace_callback('/([#!])\{([^\}]+)\}/', function ($matches) use ($attribute) {

            $subject = $matches[2];
            $code = "isset($subject) ? $subject : ''";

            if ($matches[1] !== '!')
                $code = "htmlentities($code, \\ENT_QUOTES)";

            return !$attribute ? $this->createShortCode($code) : '\'.('.$code.').\'';
        }, $string);

        $string = preg_replace_callback('/([#!])\[([^\}]+)\]/', function ($matches) use ($attribute) {

            $input = $matches[2];
            $node = $this->_parser->parse($input);

            return $this->compileNode($node);
        }, $string);

        return $string;
    }

    /**
     * Returns a new line character respecting the pretty-option
     * @return string
     */
    protected function newLine()
    {

        return $this->_options['pretty']
            ? "\n"
            : '';
    }

    /**
     * Returns indentation respecting the current level and the pretty-option
     *
     * The $offset will be added to the current level
     *
     * @param int $offset An offset added to the level
     *
     * @return string
     */
    protected function indent($offset = 0)
    {

        return $this->_options['pretty']
            ? str_repeat($this->_options['indentStyle'], ($this->_level + $offset) * $this->_options['indentWidth'])
            : '';
    }

    /**
     * Creates a PHP code expression
     * By default it will have <?php ?>-style
     *
     * @param string $code   The PHP code
     * @param string $prefix The PHP start tag
     * @param string $suffix The PHP end tag
     *
     * @return string The PHP expression
     */
    protected function createCode($code, $prefix = '<?php ', $suffix = '?>')
    {

        if (strpos($code, "\n") !== false) {

            $this->_level++;
            $code = implode($this->newLine().$this->indent(), preg_split("/\n[\t ]*/", $code))
                .$this->newLine().$this->indent(-1);
            $this->_level--;
        }

        return $prefix.$code.$suffix;
    }

    /**
     * Creates a <?=?>-style PHP expression
     *
     * @see \Tale\Jade\Compiler->createCode
     *
     * @param string $code The PHP expression to output
     *
     * @return string The PHP expression
     */
    protected function createShortCode($code)
    {

        return $this->createCode($code, '<?=');
    }

    /**
     * Creates a PHP comment surrounded by PHP code tags
     * This creates a "hidden" comment thats still visible in pretty output
     *
     * @todo Maybe this should return an empty string if pretty-option is on?
     *
     * @param string $text The text to wrap into a comment
     *
     * @return string The compiled PHP comment
     */
    protected function createPhpComment($text)
    {

        return $this->createCode($text, '<?php /* ', ' */ ?>');
    }

    /**
     * Creates a XML-style comment
     * (<!-- -->)
     *
     * @param string $text THe text to wrap into a comment
     *
     * @return string The compiled XML comment
     */
    protected function createMarkupComment($text)
    {

        return $this->createCode($text, '<!-- ', ' -->');
    }

    /**
     * Compiles any Node that has a matching method
     * for its type
     *
     * e.g.
     * type: document, method: compileDocument
     * type: element, method: compileElement
     *
     * The result will be PHTML
     *
     * @param \Tale\Jade\Parser\Node $node The Node to compile
     *
     * @return string The compiled PHTML
     * @throws Exception
     */
    protected function compileNode(Node $node)
    {

        $method = 'compile'.ucfirst($node->type);

        if (!method_exists($this, $method))
            $this->throwException(
                "No handler $method found for $node->type found",
                $node
            );

        //resolve expansions
        if (isset($node->expands)) {

            $current = $node;
            while (isset($current->expands)) {

                $expandedNode = $current->expands;
                unset($current->expands);

                $current->parent->insertBefore($current, $expandedNode);
                $current->parent->remove($current);
                $expandedNode->append($current);
                $current = $expandedNode;
            }

            return $this->compileNode($current);
        }

        return call_user_func([$this, $method], $node);
    }

    /**
     * Compiles a document Node to PHTML
     *
     * @param \Tale\Jade\Parser\Node $node The document-type node
     *
     * @return string The compiled PHTML
     */
    protected function compileDocument(Node $node)
    {

        return $this->compileChildren($node->children, false);
    }

    /**
     * Compiles a doctype Node to PHTML
     *
     * @param \Tale\Jade\Parser\Node $node The doctype-type node
     *
     * @return string The compiled PHTML
     */
    protected function compileDoctype(Node $node)
    {

        $name = $node->name;
        $value = isset($this->_options['doctypes'][$name]) ? $this->_options['doctypes'][$name] : '<!DOCTYPE '.$name.'>';

        //If doctype is XML, we switch to XML mode
        if ($name === 'xml')
            $this->_options['mode'] = self::MODE_XML;

        return $value;
    }

    /**
     * Resolves a path respecting the paths
     * set in the options as well as the last
     * element in the current $_files stack
     *
     * If no paths are given, the current get_include_path() is used
     *
     * @param string $path      The relative path to resolve
     * @param null   $extension The extension to resolve with
     *
     * @return string|false The resolved full path or false, if not found
     */
    public function resolvePath($path, $extension = null)
    {

        $paths = $this->_options['paths'];
        $ext = $extension ? $extension : $this->_options['extension'];

        if (substr($path, -strlen($ext)) !== $ext)
            $path .= $ext;

        if (count($paths) < 1) {

            //We got no paths to search in. We use the include-path in that case
            $paths = explode(\PATH_SEPARATOR, get_include_path());
        }

        if (count($this->_files) > 0)
            $paths[] = dirname(end($this->_files));

        foreach ($paths as $directory) {

            $fullPath = realpath(rtrim($directory, '/\\').'/'.ltrim($path, '/\\'));

            if ($fullPath)
                return $fullPath;
        }

        return false;
    }

    /**
     * Collects all imports and handles them via handleImport()
     *
     * @param \Tale\Jade\Parser\Node $node The root Node to search imports in
     *
     * @return $this
     * @throws Exception
     */
    protected function handleImports(Node $node)
    {

        foreach ($node->find('import') as $importNode) {

            if (!$this->_options['allowImports'])
                $this->throwException(
                    'Imports are not allowed in this compiler instance',
                    $node
                );

            $this->handleImport($importNode);
        }

        return $this;
    }

    /**
     * Loads an imported file and merges the nodes with the current tree
     *
     * @param \Tale\Jade\Parser\Node $node The node to import
     *
     * @return $this
     * @throws Exception
     */
    protected function handleImport(Node $node)
    {

        $path = $node->path;
        if ($node->importType === 'include') {

            $ext = pathinfo($path, \PATHINFO_EXTENSION);

            if (empty($ext) && $node->filter && in_array($node->filter, $this->_options['filterMap'], true)) {

                //Get our extension from our filter map
                $ext = array_search($node->filter, $this->_options['filterMap']);
            }

            if (!empty($ext) && (".$ext" !== $this->_options['extension'] || $node->filter)) {

                if (!$node->filter && isset($this->_options['filterMap'][$ext]))
                    $node->filter = $this->_options['filterMap'][$ext];

                $fullPath = $this->resolvePath($path, ".$ext");
                if (!$fullPath)
                    $this->throwException(
                        "File $path not found in ".implode(', ', $this->_options['paths']).", Include path: ".get_include_path(),
                        $node
                    );

                //remove annoying \r and \0 chars completely
                $text = trim(str_replace(["\r", "\0"], '', file_get_contents($fullPath)));

                $newNode = new Node('text');
                $newNode->value = $this->interpolate($text);

                if ($node->filter) {

                    $filter = new Node('filter');
                    $filter->name = $node->filter;
                    $filter->append($newNode);
                    $newNode = $filter;
                }

                //Notice that include might have an expansion before
                //We'd need to resolve that before we remove the import \Tale\Jade\Parser\Node alltogether
                if (isset($node->expands)) {

                    $newNode->expands = $node->expands;
                    unset($node->expands);
                }

                $node->parent->insertBefore($node, $newNode);
                $node->parent->remove($node);

                return $this;
            }
        }


        $fullPath = $this->resolvePath($path);

        if (!$fullPath)
            $this->throwException(
                "File $path wasnt found in ".implode(', ', $this->_options['paths']).", Include path: ".get_include_path(),
                $node
            );

        $importedNode = $this->_parser->parse(file_get_contents($fullPath));
        $this->_files[] = $fullPath;
        $this->handleImports($importedNode);
        array_pop($this->_files);

        //Notice that include might have an expansion before
        //We'd need to resolve that before we remove the import \Tale\Jade\Parser\Node alltogether
        if (isset($node->expands)) {

            $importedNode->expands = $node->expands;
            unset($node->expands);
        }

        $node->parent->insertBefore($node, $importedNode);
        $node->parent->remove($node);

        return $this;
    }

    /**
     * Collects all blocks and saves them into $_blocks
     * After that it calls handleBlock on each $block
     *
     * @param \Tale\Jade\Parser\Node $node The node to search blocks in
     *
     * @return $this
     */
    protected function handleBlocks(Node $node)
    {

        $this->_blocks = $node->findArray('block');
        foreach ($this->_blocks as $blockNode)
            $this->handleBlock($blockNode);

        return $this;
    }

    /**
     * Stacks blocks into each other
     * The first block found is always the container,
     * all other blocks either to append, replace or prepend
     *
     * @param \Tale\Jade\Parser\Node $node The block node to handle
     *
     * @return $this
     * @throws Exception
     */
    protected function handleBlock(Node $node)
    {

        if (!$node->name || $node->mode === 'ignore') //Will be handled through compileBlock when the loop encounters it
            return $this;

        //Find all other blocks with that name
        foreach ($this->_blocks as $block) {

            if ($block === $node || $block->name !== $node->name)
                continue;

            if ($block->expands)
                $this->throwException(
                    "It makes no sense for a sub-block to expand anything",
                    $block
                );

            $mode = $block->mode;
            //detach from parent
            $block->parent->remove($block);

            switch ($mode) {
                default:
                case 'replace':

                    $node->children = [];
                //WANTED FALLTHROUGH!
                case 'append':

                    //Append to master block
                    foreach ($block->children as $child) {

                        $block->remove($child);
                        $node->append($child);
                    }
                    break;
                case 'prepend':

                    $last = null;
                    foreach ($block->children as $child) {

                        $block->remove($child);
                        if (!$last) {

                            $node->prepend($child);
                            $last = $child;
                            continue;
                        }

                        $node->insertAfter($last, $child);
                        $last = $child;
                    }
                    break;
            }

            $block->mode = 'ignore';
        }
    }

    /**
     * Finds all mixins and loops them through handleMixin
     * Duplicated mixins will throw an exception if the replaceMixins-options
     * is false
     *
     * @param \Tale\Jade\Parser\Node $node The node to search mixins in
     *
     * @return $this
     * @throws Exception
     */
    protected function handleMixins(Node $node)
    {

        $mixins = $node->findArray('mixin');

        //Save all mixins in $this->_mixins for our mixinCalls to reference them
        foreach ($mixins as $mixinNode) {

            if (isset($this->_mixins[$mixinNode->name]) && !$this->_options['replaceMixins'])
                $this->throwException(
                    "Duplicate mixin name $mixinNode->name",
                    $mixinNode
                );

            $this->_mixins[$mixinNode->name] = $mixinNode;
        }

        //Handle the mixins
        foreach ($this->_mixins as $mixinNode) {
            $this->handleMixin($mixinNode);
        }

        return $this;
    }

    /**
     * Pre-compiles a mixin into the $_mixin array
     * Only the block content of the mixin will be compiled,
     * not the mixin itself
     *
     * The actual mixins get compiled in compileMixins
     *
     * @see \Tale\Jade\Compiler->_mixins
     * @see \Tale\Jade\Compiler->compileMixins
     *
     * @param \Tale\Jade\Parser\Node $node The mixin node to compile
     *
     * @return $this
     */
    protected function handleMixin(Node $node)
    {

        //Find the absolute document root
        $root = $node;
        while ($root->parent)
            $root = $root->parent;

        //Detach
        $node->parent->remove($node);

        $this->_mixins[$node->name] = ['node' => $node, 'phtml' => $this->compileChildren($node->children, false)];

        return $this;
    }

    /**
     * Compiles all mixins in $_mixins under each other into a single
     * PHTML block
     *
     * Mixins will be anonymous functions inside a $__mixins array
     * The mixins also pass the global $__args variable on (so that it _is_ global)
     *
     * @return string The compile PHTML
     */
    protected function compileMixins()
    {

        if (count($this->_mixins) < 1)
            return '';

        $phtml = '';
        $phtml .= $this->createCode('$__args = isset($__args) ? $__args : [];').$this->newLine();
        $phtml .= $this->createCode('$__mixins = [];').$this->newLine();

        foreach ($this->_mixins as $name => $mixin) {

            //Don't compile the mixin if we dont use it (opt-out)
            if (!$this->_options['compileUncalledMixins'] && !in_array($name, $this->_calledMixins, true))
                continue; //Skip compilation

            //Put the arguments together
            $args = [];
            $i = 0;
            $variadicIndex = null;
            $variadicName = null;
            foreach ($mixin['node']->attributes as $attr) {

                $attrName = $attr->name;
                if (strncmp('...', $attrName, 3) === 0) {

                    $variadicIndex = $i;
                    $attrName = substr($attrName, 3);
                    $variadicName = $attrName;
                }
                $args[$attrName] = trim($attr->value, '\'"');
                $i++;
            }

            $variadic = '';
            if ($variadicIndex !== null) {

                $variadic = "\n\$$variadicName = array_slice(\$__arguments, $variadicIndex);";
            }

            $phtml .= $this->createCode(
                    '$__mixins[\''.$name.'\'] = function(array $__arguments) use($__args, $__mixins) {
                    static $__defaults = '.var_export($args, true).';
                    $__arguments = array_replace($__defaults, $__arguments);
                    $__args = array_replace($__args, $__arguments);
                    extract($__args); '.$variadic.'

                '
                ).$this->newLine();

            $phtml .= $mixin['phtml'].$this->newLine();
            $phtml .= $this->createCode('};').$this->newLine();
        }

        return $phtml;
    }

    /**
     * Compiles a mixin call referencing the mixins in $_mixins
     *
     * @todo I guess the variadic handling in calls is broken right now. Calls can splat with ...
     *
     * @param \Tale\Jade\Parser\Node $node The mixin call node to compile
     *
     * @return string The compiled PHTML
     * @throws Exception
     */
    protected function compileMixinCall(Node $node)
    {

        $name = $node->name;

        if (!isset($this->_mixins[$name]))
            $this->throwException(
                "Mixin $name is not defined",
                $node
            );

        if (!in_array($name, $this->_calledMixins, true))
            $this->_calledMixins[] = $name;

        $mixin = $this->_mixins[$name];
        $phtml = '';

        if (count($node->children) > 0) {

            $phtml = $this->createCode(
                    '$__block = function(array $__arguments = []) use($__args, $__mixins) {
                extract($__args);
                extract($__arguments);
            '
                ).$this->newLine();
            $phtml .= $this->compileChildren($node->children, false).$this->newLine();
            $phtml .= $this->indent().$this->createCode('};').$this->newLine();
        }

        $nodeAttributes = $node->attributes;
        foreach ($node->assignments as $assignment) {

            $attrName = $assignment->name;

            //This line provides compatibility to the offical jade method
            if ($this->_options['mode'] === self::MODE_HTML && $attrName === 'classes')
                $attrName = 'class';

            foreach ($assignment->attributes as $attr) {

                if (!$attr->value)
                    $attr->value = $attr->name;

                $attr->name = $attrName;
                $nodeAttributes[] = $attr;
            }
        }

        $args = [];
        $i = 0;
        foreach ($nodeAttributes as $index => $attr) {

            $value = $attr->value;

            $i++;
            if ($this->isScalar($value)) {

                $value = '\''.$this->compileScalar($value).'\'';
            } else if ($this->isVariable($value)) {

                $value = "isset($value) ? $value : null";
            }

            if ($attr->name) {

                if (isset($args[$attr->name])) {

                    if (is_array($args[$attr->name]))
                        $args[$attr->name][] = $value;
                    else
                        $args[$attr->name] = [$args[$attr->name], $value];
                } else {

                    $args[$attr->name] = $value;
                }
                continue;
            }

            if (isset($mixin['node']->attributes[$index])) {

                $args[$mixin['node']->attributes[$index]->name] = $value;
                continue;
            }

            $args[] = $value;
        }

        $argCodes = [];
        foreach ($args as $key => $value) {

            $code = '\''.$key.'\' => ';

            if (is_array($value)) {

                $code .= '['.implode(', ', $value).']';
            } else {

                $code .= $value;
            }

            $argCodes[] = $code;
        }

        $phtml .= (count($node->children) > 0 ? $this->indent() : '').$this->createCode(
                '$__mixinCallArgs = ['.implode(', ', $argCodes).'];
            $__mixinCallArgs[\'__block\'] = isset($__block) ? $__block : null;
            call_user_func($__mixins[\''.$name.'\'], $__mixinCallArgs);
            unset($__mixinCallArgs);
            unset($__block);'
            ).$this->newLine();

        return $phtml;
    }

    /**
     * Compiles a block node into PHTML
     *
     * A single block node without a name or mode will act as a wrapper
     * for blocks inside mixins
     *
     * @param \Tale\Jade\Parser\Node $node The block node to compile
     *
     * @return string The compiled PHTML
     */
    protected function compileBlock(Node $node)
    {

        $name = $node->name;

        if (!$name)
            return $this->createShortCode('isset($__block) && $__block instanceof \Closure ? $__block(array_replace($__args, $__arguments)) : \'\'');

        //At this point the code knows this block only, since handleBlock took care of the blocks previously
        return $this->compileChildren($node->children, false);
    }

    /**
     * Compiles a conditional, either if, elseif, else if or else
     * into PHTML
     *
     * @param \Tale\Jade\Parser\Node $node The conditional node to compile
     *
     * @return string The compiled PHTML
     */
    protected function compileConditional(Node $node)
    {

        $type = $node->conditionType;
        $subject = $node->subject;

        if ($subject === 'block')
            $subject = '$__block';

        if ($this->isVariable($subject))
            $subject = "isset($subject) ? $subject : false";

        if ($type === 'unless') {

            $type = 'if';
            $subject = "!($subject)";
        }

        $isPrevConditional = $node->prev() && $node->prev()->type === 'conditional';
        $isNextConditional = $node->next()
            && $node->next()->type === 'conditional'
            && $node->next()->conditionType !== 'if';
        $prefix = $isPrevConditional ? '' : '<?php ';
        $suffix = $isNextConditional ? '' : '?>';
        $phtml = $type === 'else'
            ? $this->createCode(' else {', $prefix)
            : $this->createCode("$type ($subject) {", $prefix);
        $phtml .= $this->compileChildren($node->children);
        $phtml .= $this->newLine().$this->indent().$this->createCode("}", '<?php ', $suffix);

        return $phtml;
    }

    /**
     * Compiles a case-node into PHTML
     * This also checks if all sub-nodes of the case are
     * when-children, nothing else is allowed
     *
     * compileCase interacts with compileWhen to skip ?><?php after the switch {
     *
     * @param \Tale\Jade\Parser\Node $node The case node to compile
     *
     * @return string The compiled PHTML
     * @throws Exception
     */
    protected function compileCase(Node $node)
    {

        $subject = $node->subject;

        if ($this->isVariable($subject))
            $subject = "isset({$subject}) ? {$subject} : null";

        //Notice that we omit the "? >"
        //This is because PHP doesnt allow "? ><?php" between switch and the first case
        $phtml = $this->createCode("switch ({$subject}) {", '<?php ', '').$this->newLine();
        $phtml .= $this->compileChildren($node->children).$this->newLine();
        $phtml .= $this->indent().$this->createCode('}');


        //We need to check this after compilation, since there could be when: something children
        //that would be like [case children=[[something expands=[when]]] right now
        $hasChild = false;
        foreach ($node->children as $child) {

            if ($child->type !== 'when') {
                $this->throwException(
                    "`case` can only have `when` children",
                    $node
                );
            }

            $hasChild = true;
        }

        if (!$hasChild) {

            $this->throwException(
                "`case` needs at least one `when`-child",
                $node
            );
        }

        return $phtml;
    }

    /**
     * Compiles a when-node into PHTML
     * This also checks, if the when node is defined
     * on a case-parent
     *
     * When interacts with compileCase to skip the first ?><?php after the switch{
     *
     * @param \Tale\Jade\Parser\Node $node The when-node to compile
     *
     * @return string The compiled PHTML
     * @throws Exception
     */
    protected function compileWhen(Node $node)
    {

        if (!$node->parent || $node->parent->type !== 'case')
            $this->throwException(
                "`when` can only be direct descendants of `case`",
                $node
            );

        $subject = $node->subject;

        if ($subject && $this->isVariable($subject))
            $subject = "isset({$subject}) ? {$subject} : null";

        $first = $node->parent->indexOf($node) === 0;

        //If this is the first node, we omit the prefix for the code "<?php"
        //Notice that compileCase omits the ? >, so it fits together here
        $phtml = $this->createCode($node->default ? 'default:' : "case $subject:", $first ? '' : '<?php ').$this->newLine();
        $phtml .= $this->compileChildren($node->children).$this->newLine();

        if (count($node->children) > 0)
            $phtml .= $this->indent().$this->createCode('break;');

        return $phtml;
    }

    /**
     * Compiles a each-instruction into a foreach-loop
     * PHTML
     *
     * the $ in the variable names are optional
     *
     * @param \Tale\Jade\Parser\Node $node The each-node to compile
     *
     * @return string The compiled PHTML
     */
    protected function compileEach(Node $node)
    {

        static $id = 0;

        $subject = $node->subject;

        if ($this->isVariable($subject))
            $subject = "isset({$subject}) ? {$subject} : []";

        $as = "\${$node->itemName}";
        if ($node->keyName)
            $as .= " => \${$node->keyName}";

        $var = '$__iterator'.($id++);
        $phtml = $this->createCode("$var = {$subject};").$this->newLine();
        $phtml .= $this->indent().$this->createCode("foreach ($var as $as) {").$this->newLine();
        $phtml .= $this->compileChildren($node->children).$this->newLine();
        $phtml .= $this->indent().$this->createCode('}').$this->newLine();
        $phtml .= $this->indent().$this->createCode("unset($var);");

        return $phtml;
    }

    /**
     * Compiles a while-loop into PHTML
     *
     * Notice that if it has no children, we assume it's a do/while loop
     * and don't print brackets
     *
     * @todo Check for do-instruction via $node->prev()
     *
     * @param \Tale\Jade\Parser\Node $node The while-node to compile
     *
     * @return string The compiled PHTML
     */
    protected function compileWhile(Node $node)
    {

        $subject = $node->subject;

        if ($this->isVariable($subject))
            $subject = "isset({$subject}) ? {$subject} : null";

        $hasChildren = count($node->children) > 0;
        $phtml = $this->createCode("while ({$subject})".($hasChildren ? ' {' : '')).$this->newLine();
        if ($hasChildren) {

            $phtml .= $this->compileChildren($node->children).$this->newLine();
            $phtml .= $this->indent().$this->createCode('}').$this->newLine();
        }

        return $phtml;
    }

    /**
     * Compiles a do-instruction into PHTML
     *
     * @todo Check for while-node with $node->next()
     *
     * @param \Tale\Jade\Parser\Node $node The do-node to compile
     *
     * @return string The compiled PHTML
     * @throws Exception
     */
    protected function compileDo(Node $node)
    {

        $subject = $node->subject;

        if (!empty($subject))
            $this->throwException(
                "Do can't have a subject",
                $node
            );

        $phtml = $this->createCode("do {").$this->newLine();
        $phtml .= $this->compileChildren($node->children).$this->newLine();
        $phtml .= $this->indent().$this->createCode('}').$this->newLine();

        return $phtml;
    }

    /**
     * Compiles a filter-node intp PHTML
     * The filters are drawn from the filters-option
     *
     * @param \Tale\Jade\Parser\Node $node The filter node to compile
     *
     * @return string The compiled PHTML
     * @throws Exception
     */
    protected function compileFilter(Node $node)
    {

        $name = $node->name;

        if (!isset($this->_options['filters'][$name]))
            $this->throwException(
                "Filter $name doesnt exist",
                $node
            );

        $result = call_user_func($this->_options['filters'][$name], $node, $this->indent(), $this->newLine(), $this);

        return $result instanceof \Tale\Jade\Parser\Node ? $this->compileNode($result) : (string)$result;
    }

    /**
     * Compiles an array of nodes like they are children of some other node
     *
     * if $indent is true, the level will be increased
     *
     * @param array      $nodes
     * @param bool|true  $indent
     * @param bool|false $allowInline
     *
     * @return string
     */
    protected function compileChildren(array $nodes, $indent = true, $allowInline = false)
    {

        $phtml = '';
        $this->_level += $indent ? 1 : 0;

        if (count($nodes) === 1 && $allowInline) {

            $compiled = $this->compileNode($nodes[0]);
            $this->_level--;

            return trim($compiled);
        }

        foreach ($nodes as $idx => $node) {

            if ($node->type === 'text' && !$this->_options['pretty'] && $idx > 0) {

                $phtml .= ' ';
            }

            $phtml .= $this->newLine().$this->indent().$this->compileNode($node);
        }
        $this->_level -= $indent ? 1 : 0;

        return $phtml;
    }

    /**
     * Compiles an element-node containing
     * tags, attributes and assignments
     *
     * @todo Attribute escaping seems pretty broken right now
     *
     * @param \Tale\Jade\Parser\Node $node The element node to compile
     *
     * @return string The compiled PHTML
     */
    protected function compileElement(Node $node)
    {

        $phtml = '';

        if (!$node->tag)
            $node->tag = $this->_options['defaultTag'];

        $phtml .= "<{$node->tag}";


        //In the following lines we kind of map assignments
        //to attributes (that's the core of how cross-assignments work)
        //&href('a', 'b', 'c') will add 3 attributes href=a, href=b and href=b
        //to the attributes we work on
        $nodeAttributes = $node->attributes;
        foreach ($node->assignments as $assignment) {

            $name = $assignment->name;

            //This line provides compatibility to the offical jade method
            if ($this->_options['mode'] === self::MODE_HTML && $name === 'classes')
                $name = 'class';

            foreach ($assignment->attributes as $attr) {

                if (!$attr->value)
                    $attr->value = $attr->name;

                $attr->name = $name;
                $nodeAttributes[] = $attr;
            }
        }

        if (count($nodeAttributes) > 0) {

            //Iterate all attributes.
            //Multiple attributes will be put together in an array
            //and passed to the builder method
            $attributes = [];
            foreach ($nodeAttributes as $attr) {

                if (isset($attributes[$attr->name]))
                    $attributes[$attr->name][] = $attr;
                else
                    $attributes[$attr->name] = [$attr];
            }

            //first iteration of sanitizing values
            foreach ($attributes as $name => $attrs) {

                $values = [];
                $escaped = true;
                foreach ($attrs as $attr) {

                    $value = trim($attr->value);

                    if ($value) {

                        if ($this->isVariable($value)) {

                            $values[] = 'isset('.$value.') ? '.$value.' : false';
                        } else {

                            $values[] = $value;
                        }
                    }

                    if (!$attr->escaped)
                        $escaped = false;
                }

                //In HTML-mode, self-repeating attributes are automatically expanded
                if ($this->_options['mode'] === self::MODE_HTML && count($values) < 1 && in_array($name, $this->_options['selfRepeatingAttributes'])) {

                    $values[] = $name;
                }

                $quot = $this->_options['quoteStyle'];
                $builder = '\Tale\Jade\Compiler::buildValue';

                //Handle specific attribute styles for HTML
                if ($this->_options['mode'] === self::MODE_HTML) {

                    switch ($name) {
                        case 'class':
                            $builder = '\Tale\Jade\Compiler::buildClassValue';
                            break;
                        case 'style':
                            $builder = '\Tale\Jade\Compiler::buildStyleValue';
                            break;
                    }

                    if (strncmp($name, 'data-', 5) === 0)
                        $builder = '\Tale\Jade\Compiler::buildDataValue';
                }

                //If all values are scalar, we don't do any kind of resolution for
                //the attribute name. It's always there.

                $escaped = $escaped ? 'true' : 'false';

                $pair = '';
                if (count(array_filter($values, [$this, 'isScalar'])) === count($values)) {

                    //Print the normal pair
                    //We got all scalar values, we can evaluate them directly, so no code needed in the PHTML output
                    $pair .= " $name=";
                    $values = array_map(function ($val) {

                        return $this->compileScalar($val);
                    }, $values);
                    $pair .= call_user_func($builder, count($values) === 1 ? $values[0] : $values, $quot, $escaped === 'true');
                } else {

                    //If there's any kind of expression in the attribute, we
                    //also check, if something of the expression is false or null
                    //and if it is, we don't print the attribute

                    $values = array_map(function ($val) use ($quot, $builder, $escaped) {

                        return $this->isScalar($val)
                            ? call_user_func($builder, $this->compileScalar($val, true), $quot, $escaped === 'true')
                            : $val;
                    }, $values);

                    $quot = $quot === '\'' ? '\\\'' : $quot;
                    //We don't need to run big array stuff if there's only one value
                    if (count($values) === 1) {

                        $pair = $this->createCode(
                            '$__value = '.$values[0].'; '
                            .'if (!\\Tale\\Jade\\Compiler::isNullOrFalse($__value)) '
                            ."echo ' $name='.$builder(\$__value, '$quot', $escaped); "
                            .'unset($__value);'
                        );
                    } else {

                        $pair = $this->createCode(
                            '$__values = ['.implode(', ', $values).']; '
                            .'if (!\\Tale\\Jade\\Compiler::isArrayNullOrFalse($__values)) '
                            ."echo ' $name='.$builder(\$__values, '$quot', $escaped); "
                            .'unset($__values);'
                        );
                    }
                }

                $phtml .= $pair;
            }
        }

        $hasChildren = count($node->children) > 0;
        $isSelfClosing = in_array($node->tag, $this->_options['selfClosingTags']);

        if (!$hasChildren && !$isSelfClosing) {

            if ($this->_options['mode'] === self::MODE_HTML) {

                //Force closed tag in HTML
                $phtml .= "></{$node->tag}>";

                return $phtml;
            }

            //Allow /> closing in all other modes
            $phtml .= ' />';

            return $phtml;
        } else
            $phtml .= '>';

        if (!$hasChildren)
            return $phtml;

        $phtml .= $this->compileChildren($node->children);
        $phtml .= $this->newLine().$this->indent()."</{$node->tag}>";

        return $phtml;
    }

    /**
     * Compiles a text-node to PTHML
     * Texts get interpolated
     *
     * @see \Tale\Jade\Compiler->interpolate
     *
     * @param \Tale\Jade\Parser\Node $node The text-node to compile
     *
     * @return string The compiled PHTML
     */
    protected function compileText(Node $node)
    {

        return $this->interpolate($node->value).$this->compileChildren($node->children, true, true);
    }

    /**
     * Compiles an expression node and it's descending text nodes
     * into a single PHP expression
     *
     * @param \Tale\Jade\Parser\Node $node The expression node to compile
     *
     * @return string
     */
    protected function compileExpression(Node $node)
    {

        $code = $node->escaped ? 'htmlentities(%s, \\ENT_QUOTES)' : '%s';

        if (count($node->children) === 1 && $node->children[0]->type === 'text' && $this->isVariable($node->children[0]->value)) {

            //We can have a single variable expression that uses isset automatically
            $value = $node->children[0]->value;

            return $this->createShortCode(sprintf($code, "isset({$value}) ? {$value} : ''"));
        }

        $method = $node->return ? 'createShortCode' : 'createCode';

        return $this->$method(sprintf($code, trim($this->compileChildren($node->children, true, true))));
    }

    /**
     * Compiles a comment-node based on if its rendered or not
     * If it's rendered, it will be compiled as a HTML-comment,
     * if not it will be compiled as a hidden PHP comment
     *
     * @param \Tale\Jade\Parser\Node $node The comment-node to compile
     *
     * @return string The compiled PHTML
     */
    protected function compileComment(Node $node)
    {

        $content = $this->compileChildren($node->children, true, true);

        return $node->rendered ? $this->createMarkupComment($content) : $this->createPhpComment($content);
    }

    /**
     * Compiles a simple error helper in a string to be prepended to the final PHTML
     *
     * @return string The compiled PHTML for the error handler
     */
    protected function compileErrorHandlerHelper()
    {

        $phtml = '';
        if ($this->_options['handleErrors']) {

            $phtml = $this->createCode(
                    '$__errorHandler = function($code, $message, $file, $line) {

                        if (!(error_reporting() & $code))
                            return;

                        throw new \ErrorException($message, 0, $code, $file, $line);
                    };
                    set_error_handler($__errorHandler);'
                ).$this->newLine();
        }

        return $phtml;
    }

    /**
     * Throws a Tale\Jade\Parser\Exception
     *
     * @param string    $message     A meaningful exception message
     * @param Node|null $relatedNode The node the exception occured on
     *
     * @throws Exception
     */
    protected function throwException($message, \Tale\Jade\Parser\Node $relatedNode = null)
    {

        if ($relatedNode)
            $message .= "\n(".$relatedNode->type
                .' at '.$relatedNode->line
                .':'.$relatedNode->offset.')';

        throw new Exception(
            "Failed to compile Jade: $message"
        );
    }


    /**
     * Builds an attribute or argument value
     *
     * Objects get converted to arrays
     * Arrays will be imploded by '' (values are concatenated)
     *
     * ['a', 'b', ['c', ['d']]]
     * will become
     * 'abcd'
     *
     * The result will be enclosed by the quotes passed to $quoteStyle
     *
     * @param mixed  $value      The value to build
     * @param string $quoteStyle The quoting style to use
     * @param bool   $escaped    Escape the value or not
     *
     * @return string The built value
     */
    public static function buildValue($value, $quoteStyle, $escaped)
    {

        if (is_object($value))
            $value = (array)$value;

        return $quoteStyle.($escaped ? htmlentities(is_array($value) ? self::flatten($value, '') : $value, \ENT_QUOTES) : ((string)$value)).$quoteStyle;
    }

    /**
     * Builds a data-attribute value
     * If it's an object or an array, it gets converted to JSON automatically
     * If not, the value stays scalar
     *
     * JSON will automatically be enclosed by ', other results will use
     * $quoteStyle respectively
     *
     * 'a'
     * will become
     * 'a'
     *
     * ['a', 'b']
     * will become
     * '["a", "b"]' (JSON)
     *
     * @param mixed  $value      The value to build
     * @param string $quoteStyle The quoting style to use
     * @param bool   $escaped    Escape the value or not
     *
     * @return string The built value
     */
    public static function buildDataValue($value, $quoteStyle, $escaped)
    {

        if (self::isObjectOrArray($value))
            return '\''.json_encode($value).'\'';

        return $quoteStyle.($escaped ? htmlentities($value, \ENT_QUOTES) : ((string)$value)).$quoteStyle;
    }

    /**
     * Builds a style-attribute string from a value
     *
     * ['color' => 'red', 'width: 100%', ['height' => '20px']]
     * will become
     * 'color: red; width: 100%; height: 20px;'
     *
     * @param mixed  $value      The value to build
     * @param string $quoteStyle The quoting style to use
     *
     * @return string The built value
     */
    public static function buildStyleValue($value, $quoteStyle)
    {

        if (is_object($value))
            $value = (array)$value;

        if (is_array($value))
            $value = self::flatten($value, '; ', ': ');

        return $quoteStyle.((string)$value).$quoteStyle;
    }

    /**
     * Builds a class-attribute string from a value
     *
     *['a', 'b', ['c', ['d', 'e']]]
     * will become
     * 'a b c d e'
     *
     * @param mixed  $value      The value to build
     * @param string $quoteStyle The quoting style to use
     *
     * @return string The built value
     */
    public static function buildClassValue($value, $quoteStyle)
    {

        if (is_object($value))
            $value = (array)$value;

        if (is_array($value))
            $value = self::flatten($value);

        return $quoteStyle.((string)$value).$quoteStyle;
    }

    /**
     * Checks if a value is _exactly_ either null or false
     *
     * @param mixed $value The value to check
     *
     * @return bool
     */
    public static function isNullOrFalse($value)
    {

        return $value === null || $value === false;
    }

    /**
     * Checks if a whole array is _exactly_ null or false
     * (Not the array itself, but all values in the array)
     *
     * @param array $value The array to check
     *
     * @return bool
     */
    public static function isArrayNullOrFalse(array $value)
    {

        return count(array_filter($value, [self::class, 'isNullOrFalse'])) === count($value);
    }

    /**
     * Checks if a value is either an object or an array
     * (kind of like !isScalar && !isExpression)
     *
     * @param mixed $value The value to check
     *
     * @return bool
     */
    public static function isObjectOrArray($value)
    {

        return is_object($value) || is_array($value);
    }

    /**
     * Flattens an array and combines found values with $separator.
     *
     * If there are string-keys and an $argSeparator is set, it will
     * also implode those to to a single value
     *
     * With the default options
     * ['a', 'b' => 'c', ['d', 'e' => 'f', ['g' => 'h']]]
     * will become
     * 'a b=c d e=f g=h'
     *
     * @param array  $array        The array to flatten
     * @param string $separator    The separator to implode pairs with
     * @param string $argSeparator The separator to implode keys and values with
     *
     * @return string The compiled string
     */
    public static function flatten(array $array, $separator = ' ', $argSeparator = '=')
    {

        $items = [];
        foreach ($array as $key => $value) {

            if (is_object($value))
                $value = (array)$value;

            if (is_array($value))
                $value = self::flatten($value, $separator, $argSeparator);

            if (is_string($key))
                $items[] = "$key$argSeparator$value";
            else
                $items[] = $value;
        }

        return implode($separator, $items);
    }
}