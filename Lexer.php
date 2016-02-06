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

use RuntimeException;
use Tale\ConfigurableTrait;
use Tale\Jade\Lexer\Exception;
use Tale\Jade\Lexer\Reader;
use Tale\Jade\Lexer\Scanner\BlockScanner;
use Tale\Jade\Lexer\Scanner\CaseScanner;
use Tale\Jade\Lexer\Scanner\CommentScanner;
use Tale\Jade\Lexer\Scanner\FilterScanner;
use Tale\Jade\Lexer\Scanner\ImportScanner;
use Tale\Jade\Lexer\Scanner\IndentationScanner;
use Tale\Jade\Lexer\Scanner\MarkupScanner;
use Tale\Jade\Lexer\Scanner\NewLineScanner;
use Tale\Jade\Lexer\Scanner\TextLineScanner;
use Tale\Jade\Lexer\Scanner\TextScanner;
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
     * The current indentation level we are on.
     *
     * @var int
     */
    private $_level;

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
            'indentStyle' => null,
            'indentWidth' => null,
            'encoding'    => Lexer\get_internal_encoding(),
            'scanners' => [
                NewLineScanner::class, IndentationScanner::class,
                ImportScanner::class,
                BlockScanner::class,
                CaseScanner::class,
                CommentScanner::class, FilterScanner::class,

                MarkupScanner::class,
                TextLineScanner::class,
                TextScanner::class
            ],
            'scans'       => [
                'newLine', 'indent',
                'import',
                'block',
                'conditional', 'each', 'case', 'when', 'do', 'while', 'forLoop',
                'mixin', 'mixinCall',
                'doctype',
                'tag', 'classes', 'id', 'attributes',
                'assignment',
                'variable',
                'comment', 'filter',
                'expression',
                'code',
                'markup',
                'textLine',
                'text'
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

        return $this->_level;
    }

    public function setLevel($level)
    {

        if (!is_int($level))
            $this->throwException(
                "Level needs to be an integer"
            );

        $this->_level = $level;

        return $this;
    }

    public function increaseLevel()
    {

        $this->_level++;

        return $this;
    }

    public function decreaseLevel()
    {

        $this->_level--;

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

    public function addScanner($scanner)
    {

        $this->validateScanner($scanner);

        $this->_options['scanners'][] = $scanner;

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

        if (!is_null($this->_options['indentWidth']) &&
            (!is_int($this->_options['indentWidth']) || $this->_options['indentWidth'] < 1)
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
        $this->_level = 0;

        foreach ($this->loopScan($this->_options['scanners']) as $token)
            yield $token;

        $this->_reader = null;
        $this->_level = 0;
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
     * @param bool|false $required throw an exception if no tokens in $scans found anymore
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
        foreach ($scanners as $scanner) {

            $this->validateScanner($scanner);

            /** @var ScannerInterface $scanner */
            //var_dump("-> scan(".basename($scanner, 'Scanner').") -> [".$this->_reader->peek(10)."]");
            $scanner = is_string($scanner) ? new $scanner() : $scanner;
            $success = false;
            foreach ($scanner->scan($this) as $token) {

                if (!($token instanceof TokenInterface))
                    $this->throwException(
                        "Scanner generator result is not a ".TokenInterface::class
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

        //var_dump("loopScan(".implode(',', array_map('basename', $scanners, array_fill(0, count($scanners), 'Scanner'))).')');
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
     * @param string $type the type to give that token
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
     * Scans for a <case>-token.
     *
     * Case-tokens always have:
     * subject, which is the expression between the parenthesis
     *
     * @return \Generator
     */
    protected function scanCase()
    {

        return $this->scanControlStatement('case', ['case']);
    }

    /**
     * Scans for a <when>-token.
     *
     * When-tokens always have:
     * name, which is either "when" or "default"
     * subject, which is the expression behind "when ..."
     *
     * When-tokens may have:
     * default, which indicates that this is the "default"-case
     *
     * @return \Generator
     */
    protected function scanWhen()
    {

        foreach ($this->scanControlStatement('when', ['when', 'default'], 'name') as $token) {

            if ($token['type'] === 'when')
                $token['default'] = ($token['name'] === 'default');

            yield $token;
        }
    }

    /**
     * Scans for a <conditional>-token.
     *
     * Conditional-tokens always have:
     * conditionType, which is either "if", "unless", "elseif", "else if" or "else"
     * subject, which is the expression the between the parenthesis
     *
     * @return \Generator
     */
    protected function scanConditional()
    {

        return $this->scanControlStatement('conditional', [
            'if', 'unless', 'elseif', 'else if', 'else'
        ], 'conditionType');
    }

    /**
     * Scans for a control-statement-kind of token.
     *
     * e.g.
     * control-statement-name ($expression)
     *
     * Since the <each>-statement is a special little unicorn, it
     * get's handled very specifically inside this function (But correctly!)
     *
     * If the condition can have a subject, the subject
     * will be set as the "subject"-value of the token
     *
     * @param string      $type          The token type that should be created if scan is successful
     * @param array       $names         The names the statement can have (e.g. do, while, if, else etc.)
     * @param string|null $nameAttribute The attribute the name gets saved into, if wanted
     *
     * @return \Generator
     * @throws \Tale\Jade\Lexer\Exception
     */
    protected function scanControlStatement($type, array $names, $nameAttribute = null)
    {

        foreach ($names as $name) {

            if (!$this->match("{$name}[:\t \n]"))
                continue;

            $this->consumeMatch();
            $this->readSpaces();

            $token = $this->createToken($type);
            if ($nameAttribute)
                $token[$nameAttribute] = str_replace(' ', '', $name);
            $token['subject'] = null;

            //each is a special little unicorn
            if ($name === 'each') {

                if (!$this->match(
                    '\$?(?<itemName>[a-zA-Z_][a-zA-Z0-9_]*)(?:[\t ]*,[\t ]*\$?(?<keyName>[a-zA-Z_][a-zA-Z0-9_]*))?[\t ]+in[\t ]+'
                )) {
                    $this->throwException(
                        "The syntax for each is `each [$]itemName[, [$]keyName]] in [subject]`, not ".$this->peek(20)
                    );
                }

                $this->consumeMatch();
                $token['itemName'] = $this->getMatch('itemName');
                $token['keyName'] = $this->getMatch('keyName');
                $this->readSpaces();
            }

            if ($this->peek() === '(') {

                $this->consume();
                $token['subject'] = $this->readBracketContents();

                if ($this->peek() !== ')')
                    $this->throwException(
                        "Unclosed control statement subject"
                    );

                $this->consume();
            } elseif ($this->match("([^:\n]+)")) {

                $this->consumeMatch();
                $token['subject'] = trim($this->getMatch(1));
            }

            yield $token;

            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
    }

    /**
     * Scans for a <variables>-token.
     *
     * Variable-tokens always have:
     * name, which is the name of the variables to work on
     *
     * @return \Generator
     */
    protected function scanVariable()
    {

        return $this->scanToken('variable', '\$(?<name>[a-zA-Z_][a-zA-Z0-9_]*)[\t ]*');
    }

    /**
     * Scans for an <each>-token.
     *
     * Each-tokens always have:
     * itemName, which is the name of the item for each iteration
     * subject, which is the expression to iterate
     *
     * Each-tokens may have:
     * keyName, which is the name of the key for each iteration
     *
     * @return \Generator
     */
    protected function scanEach()
    {

        return $this->scanControlStatement('each', ['each']);
    }

    /**
     * Scans for a <while>-token.
     *
     * While-tokens always have:
     * subject, which is the expression between the parenthesis
     *
     * @return \Generator
     */
    protected function scanWhile()
    {

        return $this->scanControlStatement('while', ['while']);
    }

    /**
     * Scans for a <for>-token.
     *
     * For-tokens always have:
     * subject, which is the expression between the parenthesis
     *
     * @return \Generator
     */
    protected function scanForLoop()
    {

        return $this->scanControlStatement('for', ['for']);
    }

    /**
     * Scans for a <do>-token.
     *
     * Do-tokens are always stand-alone
     *
     * @return \Generator
     */
    protected function scanDo()
    {

        return $this->scanControlStatement('do', ['do']);
    }

    /**
     * Scans for a code-block initiated with a dash (-) character.
     *
     * If the dash-character stands alone on a line, a multi-line code
     * block will be opened
     *
     * Examples:
     * - if ($something):
     *     p Do something
     * - endif;
     *
     * -
     *     doSomething();
     *     doSomethingElse();
     *
     * Code-tokens always have:
     * single, which indicates that the expression is not multi-line
     *
     * @return \Generator
     */
    protected function scanCode()
    {

        foreach ($this->scanToken(
            'code',
            "\\-[\t ]*(?<value>[^\n]*)"
        ) as $token) {

            $token['value'] = trim($token['value']);
            $token['block'] = empty($token['value']);

            yield $token;

            if ($token['block']) {

                //Expect a multi-line code block
                foreach ($this->scanTextBlock() as $subToken) {

                    yield $subToken;
                }
            }
        }
    }

    /**
     * Scans for a <doctype>-token.
     *
     * Doctype-tokens always have:
     * name, which is the passed name of the doctype or a custom-doctype,
     *       if the named doctype isn't provided
     *
     * @return \Generator
     */
    protected function scanDoctype()
    {

        return $this->scanToken('doctype', "(doctype|!!!) (?<name>[^\n]*)");
    }

    /**
     * Scans for a <tag>-token.
     *
     * Tag-tokens always have:
     * name, which is the name of the tag
     *
     * @return \Generator
     */
    protected function scanTag()
    {

        foreach ($this->scanToken('tag', '(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*)', 'i') as $token) {

            yield $token;

            //Make sure classes are scanned on this before we scan the . add-on
            foreach ($this->scanClasses() as $subToken)
                yield $subToken;

            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
    }

    /**
     * Scans for a <class>-token (begins with dot (.)).
     *
     * Class-tokens always have:
     * name, which is the name of the class
     *
     * @return \Generator
     */
    protected function scanClasses()
    {

        foreach ($this->scanToken('class', '(\.(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*))', 'i') as $token) {

            yield $token;

            //Make sure classes are scanned on this before we scan the . add-on
            foreach ($this->scanClasses() as $subToken)
                yield $subToken;

            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
    }

    /**
     * Scans for a <id>-token (begins with hash (#)).
     *
     * ID-tokens always have:
     * name, which is the name of the id
     *
     * @return \Generator
     */
    protected function scanId()
    {

        foreach ($this->scanToken('id', '(#(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*))', 'i') as $token) {

            yield $token;

            //Make sure classes are scanned on this before we scan the . add-on
            foreach ($this->scanClasses() as $subToken)
                yield $subToken;

            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
    }

    /**
     * Scans for a mixin definition token (<mixin>).
     *
     * Mixin-token always have:
     * name, which is the name of the mixin you want to define
     *
     * @return \Generator
     */
    protected function scanMixin()
    {

        foreach ($this->scanToken('mixin', "mixin[\t ]+(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*)") as $token) {

            yield $token;

            //Make sure classes are scanned on this before we scan the . add-on
            foreach ($this->scanClasses() as $subToken)
                yield $subToken;

            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
    }

    /**
     * Scans for a <mixinCall>-token (begins with plus (+)).
     *
     * Mixin-Call-Tokens always have:
     * name, which is the name of the called mixin
     *
     * @return \Generator
     */
    protected function scanMixinCall()
    {

        foreach ($this->scanToken('mixinCall', '\+(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*)') as $token) {

            yield $token;

            //Make sure classes are scanned on this before we scan the . add-on
            foreach ($this->scanClasses() as $subToken)
                yield $subToken;

            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
    }

    /**
     * Scans for an <assignment>-token (begins with ampersand (&)).
     *
     * Assignment-Tokens always have:
     * name, which is the name of the assignment
     *
     * @return \Generator
     */
    protected function scanAssignment()
    {

        foreach ($this->scanToken('assignment', '&(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*)') as $token) {

            yield $token;
        }
    }

    /**
     * Scans for an attribute-block.
     *
     * Attribute blocks always consist of the following tokens:
     *
     * <attributeStart> ('(') -> Indicates that attributes start here
     * <attribute>... (name*=*value*) -> Name and Value are both optional, but one of both needs to be provided
     *                                   Multiple attributes are separated by a Comma (,) or white-space ( , \n, \t)
     * <attributeEnd> (')') -> Required. Indicates the end of the attribute block
     *
     * This function will always yield an <attributeStart>-token first, if there's an attribute block
     * Attribute-blocks can be split across multiple lines and don't respect indentation of any kind
     * except for the <attributeStart> token
     *
     * After that it will continue to yield <attribute>-tokens containing
     *  > name, which is the name of the attribute (Default: null)
     *  > value, which is the value of the attribute (Default: null)
     *  > escaped, which indicates that the attribute expression result should be escaped
     *
     * After that it will always require and yield an <attributeEnd> token
     *
     * If the <attributeEnd> is not found, this function will throw an exception
     *
     * Between <attributeStart>, <attribute>, and <attributeEnd>
     * as well as around = and , of the attributes you can utilize as many
     * spaces and new-lines as you like
     *
     * @return \Generator
     * @throws Exception
     */
    protected function scanAttributes()
    {

        if ($this->peek() !== '(')
            return;

        $argSeparators = [',', ' ', "\n", "\t"];

        $this->consume();
        yield $this->createToken('attributeStart');
        $this->read('ctype_space');

        if ($this->peek() !== ')') {

            $continue = true;
            while (!$this->isAtEnd() && $continue) {

                //We create the attribute token first (we don't need to yield it
                //but we fill it sequentially)
                $token = $this->createToken('attribute');
                $token['name'] = null;
                $token['value'] = null;
                $token['escaped'] = true;
                $token['unchecked'] = false;

                if ($this->match('((\.\.\.)?[a-zA-Z_][a-zA-Z0-9\-_:]*)', 'i')) {

                    $this->consumeMatch();

                    //If we call a php function, e.g.
                    //+button(strtoupper($someVar))
                    //the match above will match the "strtoupper" and see it
                    //as a attribute name. We'll take it as a partial value
                    //if none of our arg separators, = or ! ) follows after it
                    //TODO: strtoupper ($value) will probably still fail.
                    if (!in_array($this->peek(), array_merge($argSeparators, ['=', '!', '?', ')']), true))
                        $token['value'] = $this->getMatch(1);
                    else {

                        $token['name'] = $this->getMatch(1);
                        $this->read('ctype_space');
                    }
                }

                if ($this->match("\\/\\/[^\n]*[\n]")) {

                    //Comment line, ignore it.
                    //There'd be no senseful way to either keep or
                    //even output the comment afterwards, so we just omit it.
                    $this->consumeMatch();
                    $this->read('ctype_space');
                }

                $char = $this->peek();

                //Check unchecked-flag (?) if a name is given.
                if ($token['name'] && $char === '?') {

                    $token['unchecked'] = true;
                    $this->consume();
                    $char = $this->peek();
                }

                //Check escaping flag (!) if a name is given.
                //Avoids escaping when you call e.g.
                //+btn(!$someCondition)
                if ($token['name'] && $char === '!') {

                    $token['escaped'] = false;
                    $this->consume();
                    $char = $this->peek();
                }

                if (!$token['name'] || $char === '=') {

                    if ($char === '=') {

                        $this->consume();
                        $this->read('ctype_space');
                    }

                    $value = $this->readBracketContents($argSeparators);
                    $value = $value !== '' ? $value : null;

                    //Notice that our partial value from above kicks in here.
                    $token['value'] = $token['value'] !== null
                                    ? $token['value'].$value
                                    : $value;
                }

                yield $token;

                if (in_array($this->peek(), $argSeparators, true)) {

                    $this->consume();
                    $this->read('ctype_space');

                    $continue = $this->peek() !== ')';
                } else {

                    $continue = false;
                }
            }
        }

        if ($this->peek() !== ')')
            $this->throwException(
                "Unclosed attribute block"
            );

        $this->consume();
        yield $this->createToken('attributeEnd');

        //Make sure classes are scanned on this before we scan the . add-on
        foreach ($this->scanClasses() as $token)
            yield $token;

        foreach ($this->scanSub() as $token)
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
    protected function throwException($message)
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