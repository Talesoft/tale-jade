<?php
/**
 * The Tale Jade Lexer.
 *
 * Contains the a lexer that analyzes the input-jade and generates
 * tokens out of it via a PHP Generator
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
 * @copyright  Copyright (c) 2015 Talesoft (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.0
 * @link       http://jade.talesoft.io/docs/files/Lexer.html
 * @since      File available since Release 1.0
 */

namespace Tale\Jade;

use Tale\ConfigurableTrait;
use Tale\Jade\Lexer\Exception;
use Tale\Jade\Lexer\Reader;
use Tale\Jade\Lexer\Scanner\AssignmentScanner;
use Tale\Jade\Lexer\Scanner\AttributeScanner;
use Tale\Jade\Lexer\Scanner\BlockScanner;
use Tale\Jade\Lexer\Scanner\CaseScanner;
use Tale\Jade\Lexer\Scanner\ClassScanner;
use Tale\Jade\Lexer\Scanner\CodeScanner;
use Tale\Jade\Lexer\Scanner\CommentScanner;
use Tale\Jade\Lexer\Scanner\ConditionalScanner;
use Tale\Jade\Lexer\Scanner\DoctypeScanner;
use Tale\Jade\Lexer\Scanner\DoScanner;
use Tale\Jade\Lexer\Scanner\EachScanner;
use Tale\Jade\Lexer\Scanner\ExpressionScanner;
use Tale\Jade\Lexer\Scanner\FilterScanner;
use Tale\Jade\Lexer\Scanner\ForScanner;
use Tale\Jade\Lexer\Scanner\IdScanner;
use Tale\Jade\Lexer\Scanner\ImportScanner;
use Tale\Jade\Lexer\Scanner\IndentationScanner;
use Tale\Jade\Lexer\Scanner\MarkupScanner;
use Tale\Jade\Lexer\Scanner\MixinCallScanner;
use Tale\Jade\Lexer\Scanner\MixinScanner;
use Tale\Jade\Lexer\Scanner\NewLineScanner;
use Tale\Jade\Lexer\Scanner\TagScanner;
use Tale\Jade\Lexer\Scanner\TextLineScanner;
use Tale\Jade\Lexer\Scanner\TextScanner;
use Tale\Jade\Lexer\Scanner\VariableScanner;
use Tale\Jade\Lexer\Scanner\WhenScanner;
use Tale\Jade\Lexer\Scanner\WhileScanner;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\TokenInterface;

/**
 * Performs lexical analysis and provides a token generator.
 *
 * Tokens are defined as single units of code
 * (e.g. tag, class, id, attributeStart, attribute, attributeEnd)
 *
 * These will run through the parser and be converted to an AST
 *
 * The lexer works sequentially, ->lex will return a generator and
 * you can read that generator in any manner you like.
 * The generator will produce valid tokens until the end of the passed
 * input.
 *
 * Usage example:
 * <code>
 *
 *     use Tale\Jade\Lexer;
 *
 *     $lexer = new Lexer();
 *
 *     foreach ($lexer->lex($jadeInput) as $token)
 *          echo $token;
 *
 *     //Prints a human-readable dump of the generated tokens
 *
 * </code>
 *
 * @category   Presentation
 * @package    Tale\Jade
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Talesoft (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.0
 * @link       http://jade.talesoft.io/docs/classes/Tale.Jade.Lexer.html
 * @since      File available since Release 1.0
 */
class Lexer
{
    use ConfigurableTrait;

    const INDENT_SPACE = ' ';
    const INDENT_TAB = "\t";

    /**
     * @var Reader
     */
    private $_reader;

    /**
     * Creates a new lexer instance.
     *
     * The options should be an associative array
     *
     * Valid options are:
     *
     * indentStyle:     The indentation character (auto-detected)
     * indentWidth:     How often to repeat indentStyle (auto-detected)
     * encoding:        The encoding when working with mb_*-functions (auto-detected)
     * scans:           An array of scans that will be performed
     *
     * Passing an indentation-style forces you to stick to that style.
     * If not, the lexer will assume the first indentation type it finds as the indentation.
     * Mixed indentation is not possible, since it would be a bitch to calculate without
     * taking away configuration freedom
     *
     * Add a new scan to 'scans' to extend the lexer.
     * Notice that you need the fitting 'handle*'-method in the parser
     * or you will get unhandled-token-exceptions.
     *
     * @param array|null $options the options passed to the lexer instance
     *
     * @throws \Exception
     */
    public function __construct(array $options = null)
    {

        $this->defineOptions([
            'level' => 0,
            'indentStyle' => null,
            'indentWidth' => null,
            'encoding'    => Lexer\get_internal_encoding(),
            'scanners' => [
                'newLine' => new NewLineScanner(),
                'indent' => new IndentationScanner(),
                'import' => new ImportScanner(),
                'block' => new BlockScanner(),
                'conditional' => new ConditionalScanner(),
                'each' => new EachScanner(),
                'case' => new CaseScanner(),
                'when' => new WhenScanner(),
                'do' => new DoScanner(),
                'while' => new WhileScanner(),
                'for' => new ForScanner(),
                'mixin' => new MixinScanner(),
                'mixinCall' => new MixinCallScanner(),
                'doctype' => new DoctypeScanner(),
                'tag' => new TagScanner(),
                'class' => new ClassScanner(),
                'id' => new IdScanner(),
                'attribute' => new AttributeScanner(),
                'assignment' => new AssignmentScanner(),
                'variable' => new VariableScanner(),
                'comment' => new CommentScanner(),
                'filter' => new FilterScanner(),
                'expression' => new ExpressionScanner(),
                'code' => new CodeScanner(),
                'markup' => new MarkupScanner(),
                'textLine' => new TextLineScanner(),
                'text' => new TextScanner()
            ]
        ], $options);

        $this->_reader = null;
        $this->_level = 0;

        $this->validateIndentStyle($this->_options['indentStyle']);
        $this->validateIndentWidth($this->_options['indentWidth']);

        foreach ($this->_options['scanners'] as $scanner)
            $this->validateScanner($scanner);
    }

    /**
     * @return Reader
     */
    public function getReader()
    {
        return $this->_reader;
    }

    /**
     * Returns the current indentation level we are on.
     *
     * @return int
     */
    public function getLevel()
    {

        return $this->_options['level'];
    }

    public function setLevel($level)
    {

        if (!is_int($level))
            $this->throwException(
                "Level needs to be an integer"
            );

        $this->_options['level'] = $level;

        return $this;
    }

    /**
     * Returns the detected or previously passed indentation style.
     *
     * @return string
     */
    public function getIndentStyle()
    {

        return $this->_options['indentStyle'];
    }

    public function setIndentStyle($indentStyle)
    {

        $this->validateIndentStyle($indentStyle);
        $this->_options['indentStyle'] = $indentStyle;

        return $this;
    }

    /**
     * Returns the detected or previously passed indentation width.
     *
     * @return int
     */
    public function getIndentWidth()
    {

        return $this->_options['indentWidth'];
    }

    public function setIndentWidth($indentWidth)
    {

        $this->validateIndentWidth($indentWidth);
        $this->_options['indentWidth'] = $indentWidth;

        return $this;
    }

    public function setScanner($name, $scanner)
    {

        $this->validateScanner($scanner);

        $this->_options['scanners'][$name] = $scanner;

        return $this;
    }

    public function validateScanner($scanner)
    {

        if (!is_subclass_of($scanner, ScannerInterface::class)) {

            if (is_object($scanner))
                $scanner = get_class($scanner);

            $this->throwException(
                "Scanner $scanner is not a valid ".ScannerInterface::class
            );
        }
    }

    public function validateIndentStyle($indentStyle)
    {

        if (!in_array($indentStyle, [null, self::INDENT_TAB, self::INDENT_SPACE]))
            $this->throwException(
                "indentStyle needs to be null or one of the INDENT_* constants of the lexer"
            );
    }

    public function validateIndentWidth($indentWidth)
    {

        if (!is_null($indentWidth) &&
            (!is_int($indentWidth) || $indentWidth < 1)
        )
            $this->throwException(
                "indentWidth needs to be null or an integer above 0"
            );
    }

    /**
     * Returns a generator that will lex the passed $input sequentially.
     *
     * If you don't move the generator, the lexer does nothing.
     * Only as soon as you iterate the generator or call next()/current() on it
     * the lexer will start it's work and spit out tokens sequentially.
     * This approach takes less memory during the lexing process.
     *
     * Tokens are always an array and always provide the following keys:
     * <samp>
     * [
     *      'type' => The token type,
     *      'line' => The line this token is on,
     *      'offset' => The offset this token is at
     * ]
     * </samp>
     *
     * @param string $input the Jade-string to lex into tokens
     *
     * @return \Generator a generator that can be iterated sequentially
     */
    public function lex($input)
    {

        $this->_reader = new Reader($input, $this->_options['encoding']);
        $this->_reader->normalize();
        $startLevel = $this->getLevel();

        foreach ($this->loopScan($this->_options['scanners']) as $token)
            yield $token;

        $this->_reader = null;
        $this->setLevel($startLevel);
    }

    /**
     * Keeps scanning for all types of tokens passed as the first argument.
     *
     * If one token is encountered that's not in $scans, the function breaks
     * or throws an exception, if the second argument is true
     *
     * The passed scans get converted to methods
     * e.g. newLine => scanNewLine, blockExpansion => scanBlockExpansion etc.
     *
     * @param array|string      $scanners          the scans to perform
     *
     * @return \Generator the generator yielding all tokens found
     * @throws Exception
     */
    public function scan($scanners)
    {

        if (!$this->_reader)
            $this->throwException(
                "You need to be inside a lexing process to scan"
            );

        $scanners = is_array($scanners) ? $scanners : [$scanners];
        foreach ($scanners as $name => $scanner) {

            $this->validateScanner($scanner);

            /** @var ScannerInterface $scanner */
            $scanner = is_string($scanner) ? new $scanner() : $scanner;
            $success = false;
            foreach ($scanner->scan($this) as $token) {

                if (!($token instanceof TokenInterface))
                    $this->throwException(
                        "Scanner $name generator result is not a ".TokenInterface::class
                    );

                yield $token;
                $success = true;
            }
            $scanner = null;

            if ($success)
                return;
        }
    }

    public function loopScan($scanners, $required = false)
    {

        if (!$this->_reader)
            $this->throwException(
                "You need to be inside a lexing process to scan"
            );

        while ($this->_reader->hasLength()) {

            $success = false;
            foreach ($this->scan($scanners) as $token) {

                $success = true;
                yield $token;
            }

            if (!$success)
                break;
        }

        if ($this->_reader->hasLength() && $required)
            $this->throwException(
                "Unexpected ".$this->_reader->peek(20)
            );
    }

    /**
     * Creates a new token.
     *
     * A token is an associative array.
     * The following keys _always_ exist:
     *
     * type:    The type of the node (e.g. newLine, tag, class, id)
     * line:    The line we encountered this token on
     * offset:  The offset on a line we encountered it on
     *
     * Before adding a new token-type, make sure that the Parser knows how
     * to handle it and the Compiler knows how to compile it.
     *
     * @param string $className the class name of the token
     *
     * @return array the token array
     */
    public function createToken($className)
    {

        if (!$this->_reader)
            $this->throwException(
                "You need to be inside a lexing process to create lexer tokens"
            );

        if (!is_subclass_of($className, TokenInterface::class))
            $this->throwException(
                "$className is not a valid token class"
            );

        return new $className(
            $this->_reader->getLine(),
            $this->_reader->getOffset(),
            $this->_level
        );
    }

    public function scanToken($className, $pattern, $modifiers = null)
    {

        if (!$this->_reader)
            $this->throwException(
                "You need to be inside a lexing process to create lexer tokens"
            );

        if (!$this->_reader->match($pattern, $modifiers))
            return;

        $data = $this->_reader->getMatchData();
        $this->_reader->consume();
        $token = $this->createToken($className);
        foreach ($data as $key => $value) {

            $method = 'set'.ucfirst($key);

            if (method_exists($token, $method))
                call_user_func([$token, $method], $value);
        }

        yield $token;
    }

    /**
     * Throws a lexer-exception.
     *
     * The current line and offset of the exception
     * get automatically appended to the message
     *
     * @param string $message A meaningful error message
     *
     * @throws Exception
     */
    public function throwException($message)
    {

        $pattern = "Failed to lex jade: %s";
        $args[] = $message;

        if ($this->_reader) {

            $pattern .= " \nNear: %s \nLine: %s \nOffset: %s \nPosition: %s";
            array_push(
                $args,
                $this->_reader->peek(20),
                $this->_reader->getLine(),
                $this->_reader->getOffset(),
                $this->_reader->getPosition()
            );
        }

        throw new Exception(vsprintf($pattern, $args));
    }
}