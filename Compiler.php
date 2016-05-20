<?php
/**
 * The Tale Jade Compiler.
 *
 * Contains a compiler that takes an Abstract Syntax Tree (AST) from
 * the parser and generates Markup out of it.
 *
 * The Compiler can handle different markup-types.
 * Currently XML and HTML5 are supported.
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
 * @link       http://jade.talesoft.io/docs/files/Compiler.html
 * @since      File available since Release 1.0
 */

namespace Tale\Jade;

use Tale\ConfigurableTrait;
use Tale\Jade\Compiler\Exception;
use Tale\Jade\Parser\Node;

/**
 * Compiles an AST got from the parser to valid P/X/HTML or P/XML
 *
 * You can control the output-style via the options
 * passed to the constructor.
 *
 * Different output types are possible (Currently, XML ,HTML and XHTML)
 *
 * The main entry point is the `compile` method
 *
 * The generated PHTML/PXML should be evaluated, the best method
 * is a simple include of a generated file
 *
 * Usage example:
 * <code>
 *
 *     use Tale\Jade\Compiler;
 *
 *     $compiler = new Compiler();
 *
 *     $phtml = $compiler->compile($jadeInput);
 *     //or
 *     $phtml = $compiler->compileFile($jadeFilePath);
 *
 * </code>
 *
 * There are different approachs to handle the compiled PHTML.
 * The best and most explaining one is saving the PHTML to a file and
 * including it like this:
 *
 * <code>
 *
 *     file_put_contents('rendered.phtml', $phtml);
 *
 *    //Define some variables to pass to our template
 *    $variables = [
 *        'title' => 'My Page Title!',
 *        'posts' => []
 *    ];
 *
 *    //Make sure the variables are accessible by the included template
 *    extract($variables);
 *
 *    //Compiler needs an $__args variables to pass arguments on to mixins
 *    $__args = $variables;
 *
 *    //Include the rendered PHTML directly
 *    include('rendered.phtml');
 * </code>
 *
 * You may fetch the included content with ob_start() and ob_get_clean()
 * and pass it on to anything, e.g. a cache handler or return it as an
 * AJAX response.
 *
 * @category   Presentation
 * @package    Tale\Jade
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/classes/Tale.Jade.Compiler.html
 * @since      File available since Release 1.0
 */
class Compiler
{
    use ConfigurableTrait;

    /**
     * The Mode for HTML.
     *
     * Will     keep elements in selfClosingElements open
     * Will     repeat attributes if they're in selfRepeatingAttributes
     * Won't    /> close any elements, will </close> elements
     */
    const MODE_HTML = 0;

    /**
     * The Mode for XML.
     *
     * Will     /> close all elements, will </close> elements
     * Won't    repeat attributes if they're in selfRepeatingAttributes
     * Won't    keep elements in selfClosingElements open
     */
    const MODE_XML = 1;

    /**
     * The Mode for XHTML.
     *
     * Will     /> close all elements, will </close> elements
     * Will     repeat attributes if they're in selfRepeatingAttributes
     * Won't    keep elements in selfClosingElements open
     */
    const MODE_XHTML = 2;

    /**
     * The lexer that is given to the parser.
     *
     * @var Lexer
     */
    private $lexer;

    /**
     * The parse this compiler instance gets its nodes off.
     *
     * @var Parser
     */
    private $parser;

    /**
     * The current file stack.
     *
     * The bottom file is the file that is currently compiled.
     * This is needed for recursive path resolving in imports
     *
     * @var string[]
     */
    private $files;

    /**
     * The mixins we found in the whole input.
     *
     * We use this to check if a mixin exists upon call
     * and to compile them all at the and (with checking,
     * if they are even called)
     *
     * The array looks like this:
     * <samp>
     * [
     *     ['node' => Node, 'phtml' => <compiled phtml> ],
     *     ['node' => Node, 'phtml' => <compiled phtml> ],
     *     ['node' => Node, 'phtml' => <compiled phtml> ]
     * ]
     * </samp>
     *
     * Keys are the name of the mixin
     *
     * @var array
     */
    private $mixins;

    /**
     * A stack of names of the mixins we actually called in the code.
     *
     * @var string[]
     */
    private $calledMixins;

    /**
     * A list of all blocks in our whole input.
     *
     * They are only used in handleBlocks and handleBlock
     *
     * @var Node[]
     */
    private $blocks;

    /**
     * The level we're currently in.
     *
     * This doesn't equal the current level in the parser or lexer,
     * it rather represents the current indentation level
     * for pretty compiling
     *
     * @var int
     */
    private $level;

    /**
     * Contains the current iterator ID to avoid name collisions.
     *
     * @var int
     */
    private $iteratorId;

    /**
     * Creates a new compiler instance.
     *
     * You can pass a modified parser or lexer.
     * Notice that if you pass both, the lexer inside the parser will be used.
     *
     * Valid options are:
     *
     * pretty:                      Use indentation and new-lines
     *                              or compile everything into a single line
     * indent_style:                 The character that is used for
     *                              indentation (Space by default)
     * indent_width:                 The amount of characters to repeat for
     *                              indentation (Default 2 for 2-space-indentation)
     * self_closing_tags:             The tags that don't need any closing in
     *                              HTML-style languages
     * self_repeating_attributes:     The attributes that repeat their value to
     *                              set them to true in HTML-style languages
     * doctypes:                    The different doctypes you can use via the
     *                              "doctype"-directive [name => doctype-string]
     * mode:                        Compile in HTML, XML or XHTML mode
     * xhtmlModes:                  The mode strings that compile XHTML-style
     * filters:                     The different filters you can use via the
     *                              ":<filterName>"-directive [name => callback]
     * filter_map:                   The extension-to-filter-map for
     *                              include-filters [extension => filter]
     * escape_sequences:             The escape-sequences that are possible in
     *                              scalar strings
     * compile_uncalled_mixins:       Always compile all mixins or leave out
     *                              those that aren't called?
     * stand_alone:                  Allows the rendered files to be called
     *                              without any requirements
     * allow_imports:                Set to false to disable imports for this
     *                              compiler instance. Importing will throw an
     *                              exception. Great for demo-pages
     * defaultTag:                  The tag to default to for
     *                              class/id/attribute-initiated elements
     *                              (.abc, #abc, (abc))
     * quote_style:                  The quote-style in the markup (default: ")
     * replace_mixins:               Replaces mixins from top to bottom if they
     *                              have the same name. Allows duplicated mixin names.
     * echo_xml_doctype:              Uses PHP's "echo" to for XML processing instructions
     *                              This fixes problems with PHP's short open tags
     * paths:                       The paths to resolve paths in.
     *                              If none set, it will default to get_include_path()
     * extensions:                  The extensions for Jade files
     *                              (default: .jade and .jd)
     * parser_options:               The options for the parser if none given
     * lexer_options:                The options for the lexer if none given.
     *
     *
     * @param array|null  $options an array of options
     * @param Parser|null $parser  an existing parser instance
     * @param Lexer|null  $lexer   an existing lexer instance
     */
    public function __construct(array $options = null, Parser $parser = null, Lexer $lexer = null)
    {

        $this->defineOptions([
            'pretty'                  => false,
            'indent_style'             => Lexer::INDENT_SPACE,
            'indent_width'             => 2,
            'self_closing_tags'         => [
                'input', 'br', 'img', 'link',
                'area', 'base', 'col', 'command',
                'embed', 'hr', 'keygen', 'meta',
                'param', 'source', 'track', 'wbr'
            ],
            'self_repeating_attributes' => [
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
            'mode'                    => self::MODE_HTML,
            'xhtml_modes' => ['default', 'transitional', 'strict', 'frameset', '1.1', 'basic', 'mobile'],
            'filters'                 => [
                'plain' => 'Tale\\Jade\\Filter::filterPlain',
                'css'   => 'Tale\\Jade\\Filter::filterStyle',
                'style' => 'Tale\\Jade\\Filter::filterStyle',
                'js'    => 'Tale\\Jade\\Filter::filterScript',
                'script' => 'Tale\\Jade\\Filter::filterScript',
                'php'   => 'Tale\\Jade\\Filter::filterCode',
                'code' => 'Tale\\Jade\\Filter::filterCode',
                'markdown' => 'Tale\\Jade\\Filter::filterMarkdown',
                'md' => 'Tale\\Jade\\Filter::filterMarkdown',
                'coffeescript' => 'Tale\\Jade\\Filter::filterCoffeeScript',
                'coffee' => 'Tale\\Jade\\Filter::filterCoffeeScript',
                'less' => 'Tale\\Jade\\Filter::filterLess',
                'stylus' => 'Tale\\Jade\\Filter::filterStylus',
                'styl' => 'Tale\\Jade\\Filter::filterStylus',
                'sass' => 'Tale\\Jade\\Filter::filterSass'
                //TODO: What else?
            ],
            'filter_map'               => [
                'jade' => 'plain',
                'css'  => 'css',
                'js'   => 'js',
                'php'  => 'php',
                'md'   => 'markdown',
                'coffee' => 'coffeescript',
                'less' => 'less',
                'styl' => 'stylus',
                'sass' => 'sass',
                'scss' => 'sass'
            ],
            'escape_sequences'         => [
                '\n' => "\n",
                '\r' => "\r",
                '\t' => "\t"
            ],
            'compile_uncalled_mixins'   => false,
            'stand_alone'              => false,
            'allow_imports'            => true,
            'default_tag'              => 'div',
            'quote_style'              => '"',
            'escape_charset'           => 'UTF-8',
            'replace_mixins'           => false,
            'echo_xml_doctype'          => defined('HHVM_VERSION'),
            'paths'                   => [],
            'extensions'              => ['.jd', '.jade'],
            'parser_options'           => [],
            'lexer_options'            => []
        ], $options);

        $this->lexer = $lexer ?: new Lexer($this->options['lexer_options']);
        $this->parser = $parser ?: new Parser($this->options['parser_options'], $this->lexer);
    }

    /**
     * Returns the current lexer used.
     *
     * @return Lexer
     */
    public function getLexer()
    {

        return $this->lexer;
    }

    /**
     * Returns the current parser used.
     *
     * @return Parser
     */
    public function getParser()
    {

        return $this->parser;
    }

    /**
     * Adds a path to the compiler.
     *
     * Files will be loaded from this path (or other paths you added before)
     *
     * @param string $path the directory path
     *
     * @return $this
     */
    public function addPath($path)
    {

        $this->options['paths'][] = $path;

        return $this;
    }

    /**
     * Adds a filter to the compiler.
     *
     * This filter can then be used inside jade with the
     * :<filtername> directive
     *
     * The callback should have the following signature:
     * (\Tale\Jade\Parser\Node $node, $indent, $newLine)
     * where $node is the filter-Node found,
     * $indent is the current indentation respecting level and pretty-option
     * and newLine is a new-line respecting the pretty-option
     *
     * It should return either a PHTML string or a Node-instance
     *
     * @param string   $name     the name of the filter
     * @param callable $callback the filter handler callback
     *
     * @return $this
     */
    public function addFilter($name, $callback)
    {

        if (!is_callable($callback))
            throw new \InvalidArgumentException(
                "Argument 2 of addFilter must be valid callback"
            );

        $this->options['filters'][$name] = $callback;

        return $this;
    }

    /**
     * Compiles a Jade-string to PHTML.
     *
     * The result can then be evaluated, the best method is
     * a simple PHP include
     *
     * Look at Renderer to get this done for you
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
     * @return mixed|string a PHTML string containing HTML and PHP
     *
     * @throws Exception when the compilation fails
     * @throws Parser\Exception when the parsing fails
     * @throws Lexer\Exception when the lexing fails
     */
    public function compile($input, $path = null)
    {

        //Compiler reset
        $this->files = $path ? [$path] : [];
        $this->mixins = [];
        $this->calledMixins = [];
        $this->blocks = [];
        $this->level = 0;
        $this->iteratorId = 0;

        //Parse the input into an AST
        $node = null;
        try {

            $node = $this->parser->parse($input);
        } catch(\Exception $e) {

            //This is needed to be able to keep track of the
            //file path that is erroring
            if (!($e instanceof Exception))
                $this->throwException($e->getMessage());
            else throw $e;
        }

        //There are some things we need to take care of before compilation
        $this->handleImports($node);
        $this->handleBlocks($node);
        $this->handleMixins($node);

        //The actual compilation process ($node is the very root \Tale\Jade\Parser\Node of everything)
        $phtml = $this->compileNode($node);


        //Reset the level again for our next operations
        $this->level = 0;
        //Now we append/prepend specific stuff (like mixin functions and helpers)
        $mixins = $this->compileMixins();

        $helpers = '';
        if ($this->options['stand_alone']) {

            $helpers = file_get_contents(__DIR__.'/Compiler/functions.php')."\n?>\n";
            $helpers .= $this->createCode('namespace {');
        }

        //Put everything together
        $phtml = implode('', [$helpers, $mixins, $phtml]);

        if ($this->options['stand_alone'])
            $phtml .= $this->createCode('}');

        //Reset the files after compilation so that compileFile may resolve correctly
        //Happens when you call compileFile twice on different files
        //Note that Compiler only uses the include-path, when there is no file in the
        //file name storage $_files
        $this->files = [];

        //Return the compiled PHTML
        return trim($phtml);
    }

    /**
     * Compiles a file to PHTML.
     *
     * The given path will automatically passed as
     * compile()'s $path argument
     *
     * The path should always be relative to the paths-option paths
     *
     * @see Compiler->compile
     *
     * @param string $path the path to the jade file
     *
     * @return mixed|string the compiled PHTML
     *
     * @throws \Exception when the file is not found
     * @throws Exception when the compilation fails
     * @throws Parser\Exception when the parsing fails
     * @throws Lexer\Exception when the lexing fails
     */
    public function compileFile($path)
    {

        $fullPath = $this->resolvePath($path);

        if (!$fullPath)
            throw new \Exception(
                "File $path wasnt found in ".
                implode(', ', $this->options['paths']).
                ", Extensions: ".implode(', ', $this->options['extensions']).
                ", Include path: ".get_include_path()
            );

        return $this->compile(file_get_contents($fullPath), $fullPath);
    }

    /**
     * Checks if the current document mode equals the mode passed.
     *
     * Take a look at the Compiler::MODE_* constants to see the possible
     * modes
     *
     * @param int $mode the mode to check against
     *
     * @return bool
     */
    protected function isMode($mode)
    {

        return $this->options['mode'] === $mode;
    }

    /**
     * Checks if we're in XML document mode.
     *
     * @return bool
     */
    protected function isXml()
    {

        return $this->isMode(self::MODE_XML);
    }

    /**
     * Checks if we're in HTML document mode.
     *
     * @return bool
     */
    protected function isHtml()
    {

        return $this->isMode(self::MODE_HTML);
    }

    /**
     * Checks if we're in XHTML document mode.
     *
     * @return bool
     */
    protected function isXhtml()
    {

        return $this->isMode(self::MODE_XHTML);
    }

    /**
     * Checks if a variables is scalar (or "not an expression").
     *
     * These values don't get much special handling, they are mostly
     * simple attributes values like `type="button"` or `method='post'`
     *
     * A scalar value is either a closed string containing only
     * a-z, A-Z, 0-9, _ and -, e.g. Some-Static_Value
     * or a quote-enclosed string that can contain anything
     * except the quote style it used
     * e.g. "Some Random String", 'This can" contain quotes"'
     *
     * @param string $value the value to be checked
     *
     * @return bool
     */
    protected function isScalar($value)
    {

        return empty($value) || preg_match('/^([a-z0-9\_\-]+|"[^"]*"|\'[^\']*\')$/i', $value) ? true : false;
    }


    /**
     * Compiles and sanitizes a scalar value.
     *
     * @param string     $value  the scalar value
     * @param bool|false $inCode is this an attribute value or not
     *
     * @return string
     */
    protected function compileScalar($value, $inCode = false)
    {

        $sequences = $this->options['escape_sequences'];

        return $this->interpolate(trim(str_replace(array_keys($sequences), $sequences, $value), '\'"'), $inCode);
    }

    /**
     * Checks if a value is a variables.
     *
     * A variables needs to start with $.
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
     * @param string $value the value to be checked
     *
     * @return bool
     */
    protected function isVariable($value)
    {

        return preg_match('/^\$[a-z_\$](\$?\w*|\[[^\]]+\]|\->(\$?\w+|\{[^\}]+\}))*$/i', $value) ? true : false;
    }

    /**
     * Interpolates a string value.
     *
     * Interpolation is initialized with # (escaped) or ! (not escaped)
     *
     * After that use either {} brackets for variables expressions
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
     * @param bool|false $inCode Is this an attribute value or not
     *
     * @return string the interpolated PHTML
     */
    protected function interpolate($string, $inCode = false)
    {

        $strlen = function_exists('mb_strlen') ? 'mb_strlen': 'strlen';
        $substr = function_exists('mb_substr') ? 'mb_substr' : 'substr';

        $brackets = ['[' => ']', '{' => '}'];
        foreach ($brackets as $open => $close) {

            $match = null;
            while (preg_match(
                '/([?]?)([#!])'.preg_quote($open, '/').'/',
                $string,
                $match,
                \PREG_OFFSET_CAPTURE
            )) {

                list(, $start) = $match[0];
                list($escapeType) = $match[2];
                list($checkType) = $match[1];
                $start = $strlen(substr($string, 0, $start + 1)) - 1;
                $prefixLen = $strlen($escapeType) + $strlen($checkType) + $strlen($open);
                $offset = $start + $prefixLen;
                $level = 1;
                $subject = '';

                do {

                    $char = $substr($string, $offset, 1);

                    if ($char === $open)
                        $level++;

                    if ($char === $close) {

                        $level--;

                        if ($level === 0)
                            break;
                    }

                    $subject .= $char;
                    $offset++;
                } while ($level > 0 && $offset < $strlen($string));

                if ($offset >= $strlen($string)) {

                    $this->throwException(
                        "Failed to interpolate value, $open is not closed with $close"
                    );
                }

                $len = $prefixLen + $strlen($subject) + $strlen($close);
                $target = $substr($string, $start, $len);
                $replacement = $subject;

                switch ($open) {
                    case '{':

                        $code = $this->isVariable($subject) && $checkType !== '?'
                            ? "isset($subject) ? $subject : ''"
                            : $subject;

                        if ($escapeType !== '!')
                            $code = "htmlentities($code, \\ENT_QUOTES, '".$this->options['escape_charset']."')";

                        $replacement = !$inCode ? $this->createShortCode($code) : '\'.('.$code.').\'';
                        break;
                    case '[':

                        //This is a fix for <![endif]--> in IE conditional tags
                        if (strtolower($subject) === 'endif')
                            break 2;

                        $node = $this->parser->parse($subject);
                        $code = $this->compileNode($node);

                        if ($escapeType === '!') {

                            $code = 'htmlentities('.$this->exportScalar($code).', \\ENT_QUOTES, \''.$this->options['escape_charset'].'\')';
                            $code = !$inCode ? $this->createShortCode($code) : '\'.('.$code.').\'';
                        }

                        $replacement = $code;
                        break;

                }

                $string = str_replace($target, $replacement, $string);
            }
        }

        return $string;
    }

    /**
     * Returns a new line character respecting the pretty-option.
     *
     * @return string
     */
    protected function newLine()
    {

        return $this->options['pretty']
            ? "\n"
            : '';
    }

    /**
     * Returns indentation respecting the current level and the pretty-option.
     *
     * The $offset will be added to the current level
     *
     * @param int $offset an offset added to the level
     *
     * @return string
     */
    protected function indent($offset = 0)
    {

        return $this->options['pretty']
            ? str_repeat($this->options['indent_style'], ($this->level + $offset) * $this->options['indent_width'])
            : '';
    }

    /**
     * Creates a PHP code expression.
     *
     * By default it will have <?php ? >-style
     *
     * @param string $code   the PHP code
     * @param string $prefix the PHP start tag
     * @param string $suffix the PHP end tag
     *
     * @return string the PHP expression
     */
    protected function createCode($code, $prefix = '<?php ', $suffix = '?>')
    {

        if (strpos($code, "\n") !== false) {

            $this->level++;
            $code = implode($this->newLine().$this->indent(), preg_split("/\n[\t ]*/", $code))
                .$this->newLine().$this->indent(-1);
            $this->level--;
        }

        return $prefix.$code.$suffix;
    }

    /**
     * Creates a <?=?>-style PHP expression.
     *
     * @see Compiler->createCode
     *
     * @param string $code the PHP expression to output
     *
     * @return string The PHP expression
     */
    protected function createShortCode($code)
    {

        return $this->createCode($code, '<?=');
    }

    /**
     * Creates a PHP comment surrounded by PHP code tags.
     *
     * This creates a "hidden" comment thats still visible in the PHTML
     *
     * @todo Maybe this should return an empty string if pretty-option is on?
     *
     * @param string $text the text to wrap into a comment
     *
     * @return string the compiled PHP comment
     */
    protected function createPhpComment($text)
    {

        return $this->createCode($text, '<?php /* ', ' */ ?>');
    }

    /**
     * Creates a XML-style comment (<!-- -->).
     *
     * @param string $text the text to wrap into a comment
     *
     * @return string the compiled XML comment
     */
    protected function createMarkupComment($text)
    {

        return $this->createCode($text, '<!-- ', ' -->');
    }

    /**
     * Compiles any Node that has a matching method for its type.
     *
     * e.g.
     * type: document, method: compileDocument
     * type: element, method: compileElement
     *
     * The result will be PHTML
     *
     * @param Node $node the Node to compile
     *
     * @return string the compiled PHTML
     * @throws Exception when the compilation fails
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
     * Compiles a document Node to PHTML.
     *
     * @param Node $node The document-type node
     *
     * @return string the compiled PHTML
     */
    protected function compileDocument(Node $node)
    {

        return $this->compileChildren($node->children, false);
    }

    /**
     * Compiles a doctype Node to PHTML.
     *
     * @param Node $node the doctype-type node
     *
     * @return string the compiled PHTML
     */
    protected function compileDoctype(Node $node)
    {

        $name = $node->name;
        $value = isset($this->options['doctypes'][$name]) ? $this->options['doctypes'][$name] : '<!DOCTYPE '.$name.'>';

        if ($name === 'xml') {

            $this->options['mode'] = self::MODE_XML;

            if ($this->options['echo_xml_doctype'])
                $value = "<?='$value'?>";

        } else if (in_array($name, $this->options['xhtml_modes']))
            $this->options['mode'] = self::MODE_XHTML;
        else
            $this->options['mode'] = self::MODE_HTML;

        return $value;
    }

    /**
     * Resolves a path respecting the paths given in the options.
     *
     * The final paths for resolving are put together as follows:
     *
     * when paths options not empty      => Add paths of paths-option
     * when paths option empty           => Add paths of get_include_path()
     * when current file stack not empty => Add directory of last file we
     *                                      were compiling
     *
     * We then look for a path with the given extension inside
     * all paths we work on currently
     *
     * @param string       $path       the relative path to resolve
     * @param array|string $extensions the extensions to resolve with
     *
     * @return string|false the resolved full path or false, if not found
     */
    public function resolvePath($path, $extensions = null)
    {

        $paths = $this->options['paths'];
        $exts = $extensions ? $extensions : $this->options['extensions'];

        if (is_array($exts)) {

            foreach ($exts as $ext)
                if ($resolved = $this->resolvePath($path, $ext))
                    return $resolved;

            return false;
        }

        $ext = $exts;

        if (substr($path, -strlen($ext)) !== $ext)
            $path .= $ext;

        //Check static path
        if (file_exists($path))
            return $path;

        if (count($paths) < 1) {

            //We got no paths to search in. We use the include-path in that case
            $paths = explode(\PATH_SEPARATOR, get_include_path());
        }

        //Add the path were currently compiling in (e.g. include, extends)
        if (count($this->files) > 0)
            $paths[] = dirname(end($this->files));

        //Iterate paths and check file existence via realpath
        foreach ($paths as $directory) {

            $fullPath = realpath(rtrim($directory, '/\\').'/'.ltrim($path, '/\\'));

            if ($fullPath)
                return $fullPath;
        }

        return false;
    }

    /**
     * Collects all imports and handles them via ->handleImport.
     *
     * @param Node $node the root Node to search imports in
     *
     * @return $this
     * @throws Exception when the allowImports-options is set to false
     */
    protected function handleImports(Node $node)
    {

        foreach ($node->find('import') as $importNode) {

            if (!$this->options['allow_imports'])
                $this->throwException(
                    'Imports are not allowed in this compiler instance',
                    $node
                );

            $this->handleImport($importNode);
        }

        return $this;
    }

    /**
     * Loads an imported file and merges the nodes with the current tree.
     *
     * @param Node $node the node to import
     *
     * @return $this
     * @throws Exception
     */
    protected function handleImport(Node $node)
    {

        $path = $node->path;
        if ($node->importType === 'include') {

            $ext = pathinfo($path, \PATHINFO_EXTENSION);

            if (empty($ext) && $node->filter && in_array($node->filter, $this->options['filter_map'], true)) {

                //Get our extension from our filter map
                $ext = array_search($node->filter, $this->options['filter_map']);
            }

            if (!empty($ext) && (!in_array(".$ext", $this->options['extensions']) || $node->filter)) {

                if (!$node->filter && isset($this->options['filter_map'][$ext]))
                    $node->filter = $this->options['filter_map'][$ext];

                $fullPath = $this->resolvePath($path, ".$ext");
                if (!$fullPath)
                    $this->throwException(
                        "File $path not found in ".implode(', ', $this->options['paths']).", Include path: ".get_include_path(),
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
                "File $path wasnt found in ".implode(', ', $this->options['paths']).", Include path: ".get_include_path(),
                $node
            );

        $importedNode = $this->parser->parse(file_get_contents($fullPath));
        $this->files[] = $fullPath;
        $this->handleImports($importedNode);
        array_pop($this->files);

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
     * Collects all blocks and saves them into $_blocks.
     *
     * After that it calls handleBlock on each $block
     *
     * @param Node $node the node to search blocks in
     *
     * @return $this
     */
    protected function handleBlocks(Node $node)
    {

        $this->blocks = $node->findArray('block');
        foreach ($this->blocks as $blockNode)
            $this->handleBlock($blockNode);

        return $this;
    }

    /**
     * Stacks blocks into each other.
     *
     * The first block found is always the container,
     * all other blocks either to append, replace or prepend
     * to/the first block.
     *
     * @param Node $node the block node to handle
     *
     * @return $this
     */
    protected function handleBlock(Node $node)
    {

        if (!$node->name || $node->mode === 'ignore') //Will be handled through compileBlock when the loop encounters it
            return $this;

        //Find all other blocks with that name
        foreach ($this->blocks as $block) {

            if ($block === $node || $block->name !== $node->name)
                continue;

            $mode = $block->mode;
            //detach from parent
            $block->parent->remove($block);

            switch ($mode) {
                default:
                /** @noinspection PhpMissingBreakStatementInspection */
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

        return $this;
    }

    /**
     * Finds all mixins and loops them through handleMixin.
     *
     * Duplicated mixins will throw an exception if the replaceMixins-options
     * is false
     *
     * @param Node $node the node to search mixins in
     *
     * @return $this
     * @throws Exception when a mixin name occurs twice and replaceMixins is false
     */
    protected function handleMixins(Node $node)
    {

        $mixins = $node->findArray('mixin');

        //Save all mixins in $this->mixins for our mixinCalls to reference them
        foreach ($mixins as $mixinNode) {

            if (isset($this->mixins[$mixinNode->name]) && !$this->options['replace_mixins'])
                $this->throwException(
                    "Duplicate mixin name $mixinNode->name",
                    $mixinNode
                );

            $this->mixins[$mixinNode->name] = $mixinNode;
        }

        //Handle the mixins
        foreach ($this->mixins as $mixinNode) {
            $this->handleMixin($mixinNode);
        }

        return $this;
    }

    /**
     * Pre-compiles a mixin into the $_mixin array.
     *
     * Only the block content of the mixin will be compiled,
     * not the mixin itself
     *
     * The actual mixins get compiled in compileMixins
     *
     * @see Compiler->_mixins
     * @see Tale\Jade\Compiler->compileMixins
     *
     * @param Node $node the mixin node to compile
     *
     * @return $this
     */
    protected function handleMixin(Node $node)
    {

        //Detach
        $node->parent->remove($node);

        $this->mixins[$node->name] = [
            'node' => $node,
            'phtml' => $this->compileChildren($node->children, false)
        ];

        return $this;
    }

    /**
     * Compiles found mixins under each other into a single PHTML block.
     *
     * Mixins will be anonymous functions inside a $__mixins array
     * The mixins also pass the global $__args variables on (so that it _is_ global)
     *
     * @return string The compile PHTML
     */
    protected function compileMixins()
    {

        if (count($this->mixins) < 1)
            return '';

        $phtml = '';
        $phtml .= $this->createCode('$__args = isset($__args) ? $__args : [];').$this->newLine();
        $phtml .= $this->createCode('$__mixins = [];').$this->newLine();

        foreach ($this->mixins as $name => $mixin) {

            //Don't compile the mixin if we dont use it (opt-out)
            if (!$this->options['compile_uncalled_mixins'] && !in_array($name, $this->calledMixins, true))
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
                $args[$attrName] = $attr->value;
                $i++;
            }

            if ($variadicIndex !== null) {

                $args[$variadicName] = "array_slice(\$__arguments, $variadicIndex)";
            }

            $phtml .= $this->createCode(
                    '$__mixins[\''.$name.'\'] = function(array $__arguments) use($__args, $__mixins) {
                        $__defaults = '.$this->exportArray($args).';
                        $__arguments = array_replace($__defaults, $__arguments);
                        $__args = array_replace($__args, $__arguments);
                        extract($__args);
                    '
                ).$this->newLine();

            $phtml .= $mixin['phtml'].$this->newLine();
            $phtml .= $this->createCode('};').$this->newLine();
        }

        return $phtml;
    }

    /**
     * Compiles a mixin call referencing the mixins in $_mixins.
     *
     * @param Node $node the mixin call node to compile
     *
     * @return string The compiled PHTML
     * @throws Exception when the called mixin doesnt exist in this instance
     */
    protected function compileMixinCall(Node $node)
    {

        $name = $node->name;
        $hasBlock = false;

        if (!isset($this->mixins[$name]))
            $this->throwException(
                "Mixin $name is not defined",
                $node
            );

        if (!in_array($name, $this->calledMixins, true))
            $this->calledMixins[] = $name;

        $mixin = $this->mixins[$name];
        $phtml = '';

        if (count($node->children) > 0) {

            $hasBlock = true;
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
            if (($this->isHtml() || $this->isXml()) && $attrName === 'classes')
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

            $mixinAttributes = $mixin['node']->attributes;

            if (isset($mixinAttributes[$i]) && strncmp('...', $mixinAttributes[$i]->name, 3) !== 0) {

                $args[$mixinAttributes[$i]->name] = $value;
            } else {

                $args[] = $value;
            }
            $i++;
        }

        $phtml .= (count($node->children) > 0 ? $this->indent() : '').$this->createCode(
                '$__mixinCallArgs = '.$this->exportArray($args).';'.($hasBlock ? '
            $__mixinCallArgs[\'__block\'] = isset($__block) ? $__block : null;
            ' : '').'
            call_user_func($__mixins[\''.$name.'\'], $__mixinCallArgs);
            unset($__mixinCallArgs);
            unset($__block);'
            ).$this->newLine();

        return $phtml;
    }

    /**
     * Compiles a block node into PHTML.
     *
     * A single block node without a name or mode will act as a wrapper
     * for blocks inside mixins
     *
     * @param Node $node the block node to compile
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
     * Compiles a conditional, either if, elseif, else if or else into PHTML.
     *
     * @param Node $node the conditional node to compile
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

        $isPrevConditional = $node->prev() && $node->prev()->type === 'conditional' && $type !== 'if';
        $isNextConditional = $node->next()
            && $node->next()->type === 'conditional'
            && $node->next()->conditionType !== 'if'
            && $node->next()->conditionType !== 'unless';
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
     * Compiles a case-node into PHTML.
     *
     * This also checks if all sub-nodes of the case are
     * when-children, nothing else is allowed
     *
     * compileCase interacts with compileWhen to skip ?><?php after the switch {
     *
     * @param Node $node the case node to compile
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
     * Compiles a when-node into PHTML.
     *
     * This also checks, if the when node is defined
     * on a case-parent
     *
     * When interacts with compileCase to skip the first ?><?php after the switch{
     *
     * @param Node $node the when-node to compile
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
     * Compiles a each-instruction into a foreach-loop PHTML block.
     *
     * the $ in the variables names are optional
     *
     * @param Node $node the each-node to compile
     *
     * @return string The compiled PHTML
     */
    protected function compileEach(Node $node)
    {

        $subject = $node->subject;

        if ($this->isVariable($subject))
            $subject = "isset({$subject}) ? {$subject} : []";

        $as = "\${$node->itemName}";

        if ($node->keyName)
            $as = "\${$node->keyName} => ".$as;

        $var = '$__iterator'.($this->iteratorId++);
        $phtml = $this->createCode("$var = {$subject};").$this->newLine();
        $phtml .= $this->indent().$this->createCode("foreach ($var as $as) {").$this->newLine();
        $phtml .= $this->compileChildren($node->children).$this->newLine();
        $phtml .= $this->indent().$this->createCode('}').$this->newLine();
        $phtml .= $this->indent().$this->createCode("unset($var);");

        return $phtml;
    }

    /**
     * Compiles a while-loop into PHTML.
     *
     * Notice that if it has no children, we assume it's a do/while loop
     * and don't print brackets
     *
     * @param Node $node the while-node to compile
     *
     * @return string The compiled PHTML
     */
    protected function compileWhile(Node $node)
    {

        $subject = $node->subject;

        if ($this->isVariable($subject))
            $subject = "isset({$subject}) ? {$subject} : null";

        $hasChildren = count($node->children) > 0;
        $isDoWhile = $node->prev() && $node->prev()->type === 'do';

        if (!$hasChildren && !$isDoWhile)
            $this->throwException(
                'A while-loop without children you loop through is not valid if'
                .' there\'s no do-statement before it.',
                $node
            );
        else if ($isDoWhile && $hasChildren)
            $this->throwException(
                'In a do-while statement the while-part shouldn\'t have any children',
                $node
            );

        $phtml = $this->createCode("while ({$subject})".($hasChildren ? ' {' : ''), $isDoWhile ? ' ' : '<?php ').$this->newLine();

        if ($hasChildren) {

            $phtml .= $this->compileChildren($node->children).$this->newLine();
            $phtml .= $this->indent().$this->createCode('}').$this->newLine();
        }

        return $phtml;
    }

    /**
     * Compiles a for-loop into PHTML.
     *
     * @param Node $node the while-node to compile
     *
     * @return string The compiled PHTML
     */
    protected function compileFor(Node $node)
    {

        $subject = $node->subject;
        $phtml = $this->createCode("for ({$subject}) {").$this->newLine();
        $phtml .= $this->compileChildren($node->children).$this->newLine();
        $phtml .= $this->indent().$this->createCode('}').$this->newLine();

        return $phtml;
    }

    /**
     * Compiles a do-instruction into PHTML.
     *
     * @param Node $node the do-node to compile
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

        if (!$node->next() || $node->next()->type !== 'while')
            $this->throwException(
                "A do-statement needs a while-statement following immediately"
            );

        //Notice that the } wont have closing ? >, php needs this.
        //Check compileWhile to see the combination of both
        $phtml = $this->createCode("do {").$this->newLine();
        $phtml .= $this->compileChildren($node->children).$this->newLine();
        $phtml .= $this->indent().$this->createCode('}', '<?php ', '').$this->newLine();

        return $phtml;
    }

    /**
     * Compiles a variable-node into PHTML.
     *
     * @param Node $node the variable node to compile
     *
     * @return string The compiled PHTML
     * @throws Exception
     */
    protected function compileVariable(Node $node)
    {

        //Attribute-style assignment
        //$variable(a=b, c=d)
        if (count($node->attributes)) {

            if (count($node->children))
                $this->throwException(
                    'A variable node with attributes cant have any children',
                    $node
                );

            //Attribute-based assignment
            $array = [];
            foreach ($node->attributes as $attr) {

                $name = $attr->name;
                $value = $attr->value;

                if (!$name)
                    $array[] = $value;
                else {

                    if (isset($array[$name])) {

                        if (is_array($array[$name]))
                            $array[$name][] = $value;
                        else
                            $array[$name] = [$array[$name], $value];
                    } else
                        $array[$name] = $value;
                }
            }

            //In $array we have the final array to assign on the variable, sadly
            //we can't just use var_export, since we might have variables inside
            //the array that shouldn't be converted to strings.
            //We convert it ourself

            return $this->createCode(
                "\$__value = ".$this->exportArray($array)."; "
                ."\${$node->name} = isset(\${$node->name}) ? array_replace_recursive(\${$node->name}, \$__value) : \$__value; "
                ."unset(\$__value);"
            );
        }

        if (!count($node->children)) {

            //No children, this is simple variable output (Escaped!)
            return $this->createShortCode(
                "htmlentities(\${$node->name}, \\ENT_QUOTES, '".$this->options['escape_charset']."')"
            );
        }

        if ($node->children[0]->type !== 'expression') {

            $this->throwException(
                'Variable nodes can only have expression children',
                $node
            );
        }

        return $this->createCode("\${$node->name} = ".$node->children[0]->value);
    }

    /**
     * Compiles a filter-node into PHTML.
     *
     * The filters are drawn from the filters-option
     *
     * @param Node $node the filter node to compile
     *
     * @return string The compiled PHTML
     * @throws Exception
     */
    protected function compileFilter(Node $node)
    {

        $name = $node->name;

        if (!isset($this->options['filters'][$name]))
            $this->throwException(
                "Filter $name doesnt exist",
                $node
            );

        $result = call_user_func($this->options['filters'][$name], $node, $this->indent(), $this->newLine(), $this);

        return $result instanceof \Tale\Jade\Parser\Node ? $this->compileNode($result) : (string)$result;
    }

    /**
     * Compiles an array of nodes like they are children of some other node.
     *
     * if $indent is true, the level will be increased
     *
     * @param Node[]     $nodes
     * @param bool|true  $indent
     * @param bool|false $allowInline
     *
     * @return string
     */
    protected function compileChildren(array $nodes, $indent = true, $allowInline = false)
    {

        $phtml = '';
        $this->level += $indent ? 1 : 0;

        if (count($nodes) === 1 && $allowInline) {

            $compiled = $this->compileNode($nodes[0]);
            $this->level--;

            return trim($compiled);
        }

        foreach ($nodes as $idx => $node) {

            // Skip empty text lines completely
            if ($node->type === 'text' && trim($node->value) === '')
                continue;

            $phtml .= $this->newLine().$this->indent().$this->compileNode($node);
        }
        $this->level -= $indent ? 1 : 0;

        return $phtml;
    }

    /**
     * Compiles an element-node containing a tag, attributes and assignments.
     *
     * @todo Attribute escaping seems pretty broken right now
     *
     * @param Node $node the element node to compile
     *
     * @return string The compiled PHTML
     */
    protected function compileElement(Node $node)
    {

        $phtml = '';

        $tag = $this->interpolate($node->tag ?: $this->options['default_tag']);

        $phtml .= "<{$tag}";

        $htmlMode = $this->isHtml();
        $xhtmlMode = $this->isXhtml();
        $xmlMode = $this->isXml();
        $anyHtmlMode = $htmlMode || $xhtmlMode;
        $anyXmlMode = $xmlMode || $xhtmlMode;

        $nodeAttributes = $node->attributes;

        //In the following lines we kind of map assignments
        //to attributes (that's the core of how cross-assignments work)
        //&href('a', 'b', 'c') will add 3 attributes href=a, href=b and href=b          lkrueger
        //to the attributes we work on
        foreach ($node->assignments as $assignment) {

            $name = $assignment->name;

            //This line provides compatibility to the offical jade method
            if ($anyHtmlMode && $name === 'classes')
                $name = 'class';

            if ($anyHtmlMode && $name === 'styles')
                $name = 'style';

            if ($anyHtmlMode && $name === 'attributes') {

                foreach ($assignment->attributes as $attr) {

                    if ($attr->name) {

                        $nodeAttributes[] = $attr;
                        continue;
                    }

                    //TODO: implement cross-assigning attributes
                    //e.g. &attributes(['class' => 'abc', 'style' => ['width' => '100%']])
                    $this->throwException(
                        "Cross-assigning an array of attributes"
                        ." is not supported right now, but we're working"
                        ." on it!",
                        $node
                    );
                }
                continue;
            }

            foreach ($assignment->attributes as $attr) {

                if (!$attr->value)
                    $attr->value = $attr->name;

                $attr->name = $name;
                $nodeAttributes[] = $attr;
            }
        }

        if (count($nodeAttributes) > 0) {

            //Iterate all attributes.
            //Multiple attributes with the same name
            //will be put together in an array
            //and passed to the respective builder method
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

                        if ($this->isVariable($value) && !$attr->unchecked) {

                            $values[] = 'isset(' . $value . ') ? ' . $value . ' : false';
                        } else {

                            $values[] = $value;
                        }
                    }

                    if (!$attr->escaped)
                        $escaped = false;
                }

                //In XML-mode, self-repeating attributes are automatically expanded
                if ($anyXmlMode || $xmlMode && count($values) < 1) {

                    if ($xhtmlMode && in_array($name, $this->options['self_repeating_attributes']))
                        $values[] = $name;
                    else
                        $values[] = '';
                }

                $quot = $this->options['quote_style'];
                $builder = '\\Tale\\Jade\\Compiler\\build_value';

                //Handle specific attribute styles for HTML
                if ($anyHtmlMode) {

                    switch ($name) {
                        case 'class':
                            $builder = '\\Tale\\Jade\\Compiler\\build_class_value';
                            break;
                        case 'style':
                            $builder = '\\Tale\\Jade\\Compiler\\build_style_value';
                            break;
                    }

                    if (strncmp($name, 'data-', 5) === 0)
                        $builder = '\\Tale\\Jade\\Compiler\\build_data_value';
                }

                $escaped = $escaped ? 'true' : 'false';

                $pair = '';
                if (count($values) < 1) {

                    $pair = " $name";
                } else if (count(array_filter($values, [$this, 'isScalar'])) === count($values)) {

                    //We got all scalar values, we can evaluate them directly, so no code needed in the PHTML output
                    $pair = " $name=";
                    $values = array_map(function ($val) {

                        return $this->compileScalar($val);
                    }, $values);
                    $pair .= call_user_func($builder, count($values) === 1 ? $values[0] : $values, $quot, $escaped === 'true');
                } else {

                    //If there's any kind of expression in the attribute, we
                    //also check if something of the expression is false or null
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
                            .'if (!\\Tale\\Jade\\Compiler\\is_null_or_false($__value)) '
                            ."echo ' $name='.$builder(\$__value, '$quot', $escaped); "
                            .'unset($__value);'
                        );
                    } else {

                        $pair = $this->createCode(
                            '$__values = ['.implode(', ', $values).']; '
                            .'if (!\\Tale\\Jade\\Compiler\\is_array_null_or_false($__values)) '
                            ."echo ' $name='.$builder(\$__values, '$quot', $escaped); "
                            .'unset($__values);'
                        );
                    }
                }

                $phtml .= $pair;
            }
        }

        $hasChildren = count($node->children) > 0;
        $isSelfClosing = in_array($tag, $this->options['self_closing_tags']);

        if (!$hasChildren && (!$htmlMode || !$isSelfClosing)) {

            if ($anyHtmlMode && !$isSelfClosing) {

                //Force closed tag in HTML
                $phtml .= "></{$tag}>";

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
        $phtml .= $this->newLine().$this->indent()."</{$tag}>";

        return $phtml;
    }

    /**
     * Compiles a text-node to PTHML.
     *
     * Texts get interpolated
     *
     * @see Compiler->interpolate
     *
     * @param Node $node the text-node to compile
     *
     * @return string The compiled PHTML
     */
    protected function compileText(Node $node)
    {

        //Dont print empty text
        if ($node->escaped)
            $text = $this->createShortCode(
                'htmlentities('.$this->exportScalar($node->value, '\'', true).', \\ENT_QUOTES, \''.$this->options['escape_charset'].'\')'
            );
        else
            $text = $this->interpolate($node->value);

        return $text.$this->compileChildren($node->children, true, true);
    }

    /**
     * Compiles an expression node and into a PHP expression.
     *
     * @param Node $node the expression node to compile
     *
     * @return string
     */
    protected function compileExpression(Node $node)
    {

        $code = $node->escaped ? 'htmlentities(%s, \\ENT_QUOTES, \''.$this->options['escape_charset'].'\')' : '%s';

        $value = rtrim(trim($node->value), ';');

        if ($this->isVariable($value) && !$node->unchecked)
            $value = "isset({$value}) ? {$value} : ''";

        return $this->createShortCode(sprintf($code, $value));
    }

    /**
     * Compiles a code node and it's descending text nodes
     * into a single PHP code block.
     *
     * @param Node $node the code node to compile
     *
     * @return string
     */
    protected function compileCode(Node $node)
    {

        if (!$node->block) {

            return $this->createCode($node->value)
                  .$this->newLine()
                  .$this->compileChildren($node->children, true, true);
        }

        return $this->createCode(trim($this->compileChildren($node->children, true, true)));
    }

    /**
     * Compiles a comment-node based on if its rendered or not.
     *
     * If it's rendered, it will be compiled as a HTML-comment,
     * if not it will be compiled as a hidden PHP comment
     *
     * @param Node $node the comment-node to compile
     *
     * @return string The compiled PHTML
     */
    protected function compileComment(Node $node)
    {

        $content = $this->compileChildren($node->children, true, true);

        return $node->rendered ? $this->createMarkupComment($content) : $this->createPhpComment($content);
    }

    /**
     * Exports an array to a PHP-string recursively.
     *
     * This works similar to var_export in PHP, with the difference
     * that it won't try to convert PHP-expression-style strings,
     * e.g. variables, scalar values like null, false, true and
     * expressions like arrays or function calls
     *
     * @param array $array the array to export
     * @param string $quoteStyle the quote-style used, ' by default
     *
     * @return string the exported array
     */
    protected function exportArray(array $array, $quoteStyle = '\'')
    {

        $pairs = [];
        foreach ($array as $key => $val) {

            $pair = $this->exportScalar($key, $quoteStyle).' => ';

            if (is_array($val))
                $pair .= $this->exportArray($val, $quoteStyle);
            else if ($this->isVariable($val))
                $pair .= "isset($val) ? $val : null";
            else if ($this->isScalar($val) || in_array($val, [true, false, null], true))
                $pair .= $this->exportScalar($val, $quoteStyle);
            else
                $pair .= (string)$val;

            $pairs[] = $pair;
        }

        return '['.implode(', ', $pairs).']';
    }

    /**
     * Exports a scalar value to the PHP representation.
     *
     * This also takes into account the PHP constants
     * null, false and true, makes sure that numeric-values aren't
     * string enclosed and utilizes interpolation for string
     * values.
     *
     * @param mixed $scalar the scalar value to export
     * @param string $quoteStyle the quote-style used, ' by default
     *
     * @return string the exported scalar value
     */
    protected function exportScalar($scalar, $quoteStyle = '\'', $inCode = false)
    {

        if ($scalar === 'null' || $scalar === null)
            return 'null';

        if ($scalar === 'false' || $scalar === false)
            return 'false';

        if ($scalar === 'true' || $scalar === true)
            return 'true';

        $scalar = trim($scalar, '\'"');

        if (is_numeric($scalar))
            return $scalar;

        return $quoteStyle.$this->compileScalar($scalar, $inCode).$quoteStyle;
    }

    /**
     * Throws a Compiler-Exception.
     *
     * @param string    $message     A meaningful exception message
     * @param Node|null $relatedNode The node the exception occured on
     *
     * @throws Exception
     */
    protected function throwException($message, Node $relatedNode = null)
    {

        if ($relatedNode)
            $message .= "\n(".$relatedNode->type
                .' at '.$relatedNode->line
                .':'.$relatedNode->offset.')';

        if (!empty($this->files))
            $message .= "\n[".end($this->files).']';

        throw new Exception(
            "Failed to compile Jade: $message"
        );
    }
}