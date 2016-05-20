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
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/files/Lexer.html
 * @since      File available since Release 1.0
 */

namespace Tale\Jade;

use RuntimeException;
use Tale\ConfigurableTrait;
use Tale\Jade\Lexer\Exception;

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
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/classes/Tale.Jade.Lexer.html
 * @since      File available since Release 1.0
 */
class Lexer
{
    use ConfigurableTrait;

    /**
     * Tab Indentation (\t)
     */
    const INDENT_TAB = "\t";

    /**
     * Space Indentation ( )
     */
    const INDENT_SPACE = ' ';

    /**
     * The current input string.
     *
     * @var string
     */
    private $input;

    /**
     * The total length of the current input.
     *
     * @var int
     */
    private $length;

    /**
     * The current position inside the input string.
     *
     * @var int
     */
    private $position;

    /**
     * The current line we are on.
     *
     * @var int
     */
    private $line;

    /**
     * The current offset in a line we are on.
     *
     * Resets on each new line and increases on each read character
     *
     * @var int
     */
    private $offset;

    /**
     * The current indentation level we are on.
     *
     * @var int
     */
    private $level;

    /**
     * The current indentation character.
     *
     * @var string
     */
    private $indentStyle;

    /**
     * The width of the indentation.
     *
     * Specifies how often $_indentStyle
     * is repeated for each $_level
     *
     * @var string
     */
    private $indentWidth;

    /**
     * The last result gotten via ->peek().
     *
     * @see Lexer->peek
     * @var string
     */
    private $lastPeekResult;

    /**
     * The last matches gotten via ->match()
     *
     * @see Lexer->match
     * @var array
     */
    private $lastMatches;

    /**
     * Creates a new lexer instance.
     *
     * The options should be an associative array
     *
     * Valid options are:
     *
     * indentStyle:     The indentation character (auto-detected)
     * indentWidth:     How often to repeat indentStyle (auto-detected)
     * encoding:        The encoding when working with mb_*-functions (Default: UTF-8)
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
            'indent_style' => null,
            'indent_width' => null,
            'encoding'    => mb_internal_encoding(),
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

        //Validate options
        if (!in_array($this->options['indent_style'], [null, self::INDENT_TAB, self::INDENT_SPACE]))
            throw new RuntimeException(
                "indentStyle needs to be null or one of the INDENT_* constants of the lexer"
            );

        if (!is_null($this->options['indent_width']) &&
            (!is_int($this->options['indent_width']) || $this->options['indent_width'] < 1)
        )
            throw new RuntimeException(
                "indentWidth needs to be a integer above 0"
            );
    }

    /**
     * Returns the current input-string worked on.
     *
     * @return string
     */
    public function getInput()
    {

        return $this->input;
    }

    /**
     * Returns the total length of the current input-string.
     *
     * @return int
     */
    public function getLength()
    {

        return $this->length;
    }

    /**
     * Returns the total position in the current input-string.
     *
     * @return int
     */
    public function getPosition()
    {

        return $this->position;
    }

    /**
     * Returns the line we are working on in the current input-string.
     *
     * @return int
     */
    public function getLine()
    {

        return $this->line;
    }

    /**
     * Gets the offset on a line (Line-start is 0) in the current input-string.
     *
     * @return int
     */
    public function getOffset()
    {

        return $this->offset;
    }

    /**
     * Returns the current indentation level we are on.
     *
     * @return int
     */
    public function getLevel()
    {

        return $this->level;
    }

    /**
     * Returns the detected or previously passed indentation style.
     *
     * @return string
     */
    public function getIndentStyle()
    {

        return $this->indentStyle;
    }

    /**
     * Returns the detected or previously passed indentation width.
     *
     * @return int
     */
    public function getIndentWidth()
    {

        return $this->indentWidth;
    }

    /**
     * Returns the last result of ->peek().
     *
     * @see Lexer->peek
     * @return string|null
     */
    public function getLastPeekResult()
    {

        return $this->lastPeekResult;
    }

    /**
     * Returns the last array of matches through ->match.
     *
     * @see Lexer->match
     * @return array|null
     */
    public function getLastMatches()
    {

        return $this->lastMatches;
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

        $this->input = rtrim(str_replace([
            "\r", "\0"
        ], '', $input))."\n";
        $this->length = $this->strlen($this->input);
        $this->position = 0;

        $this->line = 1;
        $this->offset = 0;
        $this->level = 0;

        $this->indentStyle = $this->options['indent_style'];
        $this->indentWidth = $this->options['indent_width'];

        $this->lastPeekResult = null;
        $this->lastMatches = null;

        foreach ($this->scanFor($this->options['scans'], true) as $token)
            yield $token;
    }

    /**
     * Dumps jade-input into a set of string-represented tokens.
     *
     * This makes debugging the lexer easier.
     *
     * @param string $input the jade input to dump the tokens of
     */
    public function dump($input)
    {

        foreach ($this->lex($input) as $token) {

            $type = $token['type'];
            $line = $token['line'];
            $offset = $token['offset'];
            unset($token['type'], $token['line'], $token['offset']);

            echo "[$type($line:$offset)";
            $vals = implode(', ', array_map(function ($key, $value) {

                return "$key=$value";
            }, array_keys($token), $token));

            if (!empty($vals))
                echo " $vals";

            echo ']';

            if ($type === 'newLine')
                echo "\n";
        }
    }

    /**
     * Checks if our read pointer is at the end of the code.
     *
     * @return bool
     */
    protected function isAtEnd()
    {

        return $this->position >= $this->length;
    }

    /**
     * Shows the next characters in our input.
     *
     * Pass a $length to get more than one character.
     * The character's _won't_ be consumed here, they are just shown.
     * The position pointer won't be moved forward
     *
     * The result gets saved in $_lastPeekResult
     *
     * @param int $length the length of the string we want to peek on
     *
     * @return string the peeked string
     */
    protected function peek($length = 1)
    {

        $this->lastPeekResult = $this->substr($this->input, 0, $length);

        return $this->lastPeekResult;
    }

    /**
     * Consumes a length or the length of the last peeked string.
     *
     * Internally $input = substr($input, $length) is done,
     * so everything _before_ the consumed length will be cut off and
     * removed from the RAM (since we probably tokenized it already,
     * remember? sequential shit etc.?)
     *
     * @see Lexer->peek
     *
     * @param int|null $length the length to consume or null, to use the length of the last peeked string
     *
     * @return $this
     * @throws Exception
     */
    protected function consume($length = null)
    {

        if ($length === null) {

            if ($this->lastPeekResult === null)
                $this->throwException(
                    "Failed to consume: Nothing has been peeked and you"
                    ." didnt pass a length to consume"
                );

            $length = $this->strlen($this->lastPeekResult);
        }

        $this->input = $this->substr($this->input, $length);
        $this->position += $length;
        $this->offset += $length;

        return $this;
    }

    /**
     * Peeks and consumes chars until the passed callback returns false.
     *
     * The callback takes the current character as the first argument.
     *
     * This works great with ctype_*-functions
     *
     * If the last character doesn't match, it also won't be consumed
     * You can always go on reading right after a call to ->read()
     *
     * e.g.
     * $alNumString = $this->read('ctype_alnum')
     * $spaces = $this->read('ctype_space')
     *
     * @param callable $callback the callback to check the current character against
     * @param int      $length   the length to peek. This will also increase the length of the characters passed to the callback
     *
     * @return string the read string
     * @throws \Exception
     */
    protected function read($callback, $length = 1)
    {

        if (!is_callable($callback))
            throw new \Exception(
                "Argument 1 passed to peekWhile needs to be callback"
            );

        $result = '';
        while (!$this->isAtEnd() && $callback($this->peek($length))) {

            //Keep $_line and $_offset updated
            $newLines = $this->substr_count($this->lastPeekResult, "\n");
            $this->line += $newLines;

            if ($newLines) {

                if (strlen($this->lastPeekResult) === 1)
                    $this->offset = 0;
                else {

                    $parts = explode("\n", $this->lastPeekResult);
                    $this->offset = strlen($parts[count($parts) - 1]) - 1;
                }
            }

            $this->consume();
            $result .= $this->lastPeekResult;
        }

        return $result;
    }

    /**
     * Reads all TAB (\t) and SPACE ( ) chars until something else is found.
     *
     * This is primarily used to parse the indentation
     * at the begin of each line.
     *
     * @return string the spaces that have been found
     * @throws Exception
     */
    protected function readSpaces()
    {

        return $this->read(function ($char) {

            return $char === self::INDENT_SPACE || $char === self::INDENT_TAB;
        });
    }

    /**
     * Reads a "value", 'value' or value style string really gracefully.
     *
     * It will stop on all chars passed to $breakChars as well as a closing ')'
     * when _not_ inside an expression initiated with either
     * ", ', (, [ or {.
     *
     * $breakChars might be [','] as an example to read sequential arguments
     * into an array. Scan for ',', skip spaces, repeat readBracketContents
     *
     * Brackets are counted, strings are respected.
     *
     * Inside a " string, \" escaping is possible, inside a ' string, \' escaping
     * is possible
     *
     * As soon as a ) is found and we're outside a string and outside any kind of bracket,
     * the reading will stop and the value, including any quotes, will be returned
     *
     * Examples:
     * ('`' marks the parts that are read, understood and returned by this function)
     *
     * <samp>
     *      (arg1=`abc`, arg2=`"some expression"`, `'some string expression'`)
     *      some-mixin(`'some arg'`, `[1, 2, 3, 4]`, `(isset($complex) ? $complex : 'complex')`)
     *      and even
     *      some-mixin(callback=`function($input) { return trim($input, '\'"'); }`)
     * </samp>
     *
     * @param array|null $breakChars the chars to break on.
     *
     * @return string the (possibly quote-enclosed) result string
     */
    protected function readBracketContents(array $breakChars = null)
    {

        $breakChars = $breakChars ? $breakChars : [];
        $value = '';
        $prev = null;
        $char = null;
        $level = 0;
        $inString = false;
        $stringType = null;
        $finished = false;
        while (!$this->isAtEnd() && !$finished) {

            if ($this->isAtEnd())
                break;

            $prev = $char;
            $char = $this->peek();

            switch ($char) {
                case '"':
                case '\'':

                    if ($inString && $stringType === $char && $prev !== '\\')
                        $inString = false;
                    else if (!$inString) {

                        $inString = true;
                        $stringType = $char;
                    }
                    break;
                case '(':
                case '[':
                case '{':

                    if (!$inString)
                        $level++;
                    break;
                case ')':
                case ']':
                case '}':

                    if ($inString)
                        break;

                    if ($level === 0) {

                        $finished = true;
                        break;
                    }

                    $level--;
                    break;
            }

            if (in_array($char, $breakChars, true) && !$inString && $level === 0)
                $finished = true;

            if (!$finished) {

                $value .= $char;
                $this->consume();
            }
        }

        return trim($value);
    }

    /**
     * Matches a pattern against the start of the current $input.
     *
     * Notice that this always takes the start of the current pointer
     * position as a reference, since `consume` means cutting of the front
     * of the input string
     *
     * After a match was successful, you can retrieve the matches
     * with ->getMatch() and consume the whole match with ->consumeMatch()
     *
     * ^ gets automatically prepended to the pattern (since it makes no sense
     * for a sequential lexer to search _inside_ the input)
     *
     * @param string $pattern   the regular expression without delimeters and a ^-prefix
     * @param string $modifiers the usual PREG RegEx-modifiers
     *
     * @return bool
     */
    protected function match($pattern, $modifiers = '')
    {

        return preg_match(
            "/^$pattern/$modifiers",
            $this->input,
            $this->lastMatches
        ) ? true : false;
    }

    /**
     * Consumes a match previously read and matched by ->match().
     *
     * @see Lexer->match
     * @return $this
     */
    protected function consumeMatch()
    {

        //Make sure we don't consume matched newlines (We match for them sometimes)
        //We need the newLine tokens and don't want them consumed here.
        $match = $this->lastMatches[0] !== "\n" ? rtrim($this->lastMatches[0], "\n") : $this->lastMatches[0];

        return $this->consume($this->strlen($match));
    }

    /**
     * Gets a match from the last ->match() call
     *
     * @see Lexer->match
     *
     * @param int|string $index the index of the usual PREG $matches argument
     *
     * @return mixed|null the value of the match or null, if none found
     */
    protected function getMatch($index)
    {

        return isset($this->lastMatches[$index]) ? $this->lastMatches[$index] : null;
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
     * @param array      $scans          the scans to perform
     * @param bool|false $throwException throw an exception if no tokens in $scans found anymore
     *
     * @return \Generator the generator yielding all tokens found
     * @throws Exception
     */
    protected function scanFor(array $scans, $throwException = false)
    {

        while (!$this->isAtEnd()) {

            $found = false;
            foreach ($scans as $name) {

                foreach (call_user_func([$this, 'scan'.ucfirst($name)]) as $token) {

                    $found = true;
                    yield $token;
                }

                if ($found)
                    continue 2;
            }

            $spaces = $this->readSpaces();
            if (!empty($spaces) && !$this->isAtEnd())
                continue;

            if ($throwException) {

                $this->throwException(
                    'Unexpected `'.htmlentities($this->peek(20), \ENT_QUOTES).'`, '
                    .implode(', ', $scans).' expected'
                );
            } else
                return;
        }
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
    protected function createToken($type)
    {

        return [
            'type'   => $type,
            'line'   => $this->line,
            'level'  => $this->level,
            'offset' => $this->offset
        ];
    }

    /**
     * Scans for a specific token-type based on a pattern
     * and converts it to a valid token automatically.
     *
     * All matches that have a name (RegEx (?<name>...)-directive
     * will directly get a key with that name and value
     * on the token array
     *
     * For matching, ->match() is used internally
     *
     * @see Lexer->match
     *
     * @param string $type      the token type to create, if matched
     * @param string $pattern   the pattern to match
     * @param string $modifiers the regex-modifiers for the pattern
     *
     * @return \Generator
     */
    protected function scanToken($type, $pattern, $modifiers = '')
    {

        if (!$this->match($pattern, $modifiers))
            return;

        $this->consumeMatch();
        $token = $this->createToken($type);
        foreach ($this->lastMatches as $key => $value) {

            //We append all STRING-Matches (?<name>) to the token
            if (is_string($key)) {

                $token[$key] = empty($value) ? null : $value;
            }
        }

        yield $token;
    }

    /**
     * Scans for indentation and automatically keeps
     * the $_level updated through all tokens.
     *
     * Upon reaching a higher level, an <indent>-token is
     * yielded, upon reaching a lower level, an <outdent>-token is yielded
     *
     * If you outdented 3 levels, 3 <outdent>-tokens are yielded
     *
     * The first indentation this function encounters will be used
     * as the indentation style for this document.
     *
     * You can indent with everything between 1 space and a few million tabs
     * other than most Jade implementations
     *
     * @return \Generator|void
     * @throws Exception
     */
    protected function scanIndent()
    {

        if ($this->offset !== 0 || !$this->match("([\t ]*)"))
            return;

        $this->consumeMatch();
        $indent = $this->getMatch(1);

        //If this is an empty line, we ignore the indentation completely.
        foreach ($this->scanNewLine() as $token) {

            yield $token;

            return;
        }

        $oldLevel = $this->level;
        if (!empty($indent)) {

            $spaces = $this->strpos($indent, ' ') !== false;
            $tabs = $this->strpos($indent, "\t") !== false;
            $mixed = $spaces && $tabs;

            if ($mixed) {

                switch ($this->indentStyle) {
                    case self::INDENT_SPACE:
                    default:

                        //Convert tabs to spaces based on indentWidth
                        $spaces = str_replace(self::INDENT_TAB, str_repeat(
                            self::INDENT_SPACE,
                            $this->indentWidth ? $this->indentWidth : 4
                        ), $spaces);
                        $tabs = false;
                        $mixed = false;
                        break;
                    case self::INDENT_TAB:

                        //Convert spaces to tabs
                        $spaces = str_replace(self::INDENT_SPACE, str_repeat(
                            self::INDENT_TAB,
                            $this->indentWidth ? $this->indentWidth : 1
                        ), $spaces);
                        $spaces = false;
                        $mixed = false;
                        break;
                }
            }

            //Validate the indentation style
            $this->indentStyle = $tabs ? self::INDENT_TAB : self::INDENT_SPACE;

            //Validate the indentation width
            if (!$this->indentWidth)
                //We will use the pretty first indentation as our indent width
                $this->indentWidth = $this->strlen($indent);

            $this->level = intval(round($this->strlen($indent) / $this->indentWidth));

            if ($this->level > $oldLevel + 1)
                $this->level = $oldLevel + 1;
        } else
            $this->level = 0;

        $levels = $this->level - $oldLevel;

        //Unchanged levels
        if (!empty($indent) && $levels === 0)
            return;

        //We create a token for each indentation/outdentation
        $type = $levels > 0 ? 'indent' : 'outdent';
        $levels = abs($levels);

        while ($levels--)
            yield $this->createToken($type);
    }

    /**
     * Scans for a new-line character and yields a <newLine>-token if found.
     *
     * @return \Generator
     */
    protected function scanNewLine()
    {

        foreach ($this->scanToken('newLine', "\n") as $token) {

            $this->line++;
            $this->offset = 0;
            yield $token;
        }
    }

    /**
     * Scans for text until the end of the current line
     * and yields a <text>-token if found.
     *
     * @param bool $escaped
     * @return \Generator
     */
    protected function scanText($escaped = false)
    {

        foreach ($this->scanToken('text', "[ ]?([^\n]*)?") as $token) {

            $value = trim($this->getMatch(1), "\t");

            $token['value'] = $value;
            $token['escaped'] = $escaped;
            yield $token;
        }
    }


    /**
     * Scans for text and keeps scanning text, if you indent once
     * until it is outdented again (e.g. .-text-blocks, expressions, comments).
     *
     * Yields anything between <text>, <newLine>, <indent> and <outdent> tokens
     * it encounters
     *
     * @return \Generator
     */
    protected function scanTextBlock($escaped = false)
    {

        foreach ($this->scanText($escaped) as $token)
            yield $token;

        foreach ($this->scanNewLine() as $token)
            yield $token;

        if ($this->isAtEnd())
            return;

        $level = 0;
        do {

            foreach ($this->scanFor(['newLine', 'indent']) as $token) {

                if ($token['type'] === 'indent')
                    $level++;

                if ($token['type'] === 'outdent')
                    $level--;

                yield $token;
            }

            if ($level <= 0)
                continue;

            foreach ($this->scanText($escaped) as $token)
                yield $token;

            foreach ($this->scanNewLine() as $token)
                yield $token;

        } while (!$this->isAtEnd() && $level > 0);
    }

    /**
     * Scans for a |-style text-line and yields it along
     * with a text-block, if it has any.
     *
     * @return \Generator
     */
    protected function scanTextLine()
    {

        if (!$this->match('([!]?)\|'))
            return;

        $this->consumeMatch();

        foreach ($this->scanTextBlock($this->getMatch(1) === '!') as $token)
            yield $token;
    }

    /**
     * Scans for HTML-markup based on a starting '<'.
     *
     * The whole markup will be kept and yielded
     * as a <text>-token
     *
     * @return \Generator
     */
    protected function scanMarkup()
    {

        if ($this->peek() !== '<')
            return;

        foreach ($this->scanText() as $token)
            yield $token;
    }

    /**
     * Scans for //-? comments yielding a <comment>
     * token if found as well as a stack of text-block tokens.
     *
     * @return \Generator
     */
    protected function scanComment()
    {

        if (!$this->match("\\/\\/(-)?[\t ]*"))
            return;

        $this->consumeMatch();

        $token = $this->createToken('comment');
        $token['rendered'] = $this->getMatch(1) ? false : true;

        yield $token;

        foreach ($this->scanTextBlock() as $token)
            yield $token;
    }

    /**
     * Scans for :<filterName>-style filters and yields
     * a <filter> token if found.
     *
     * Filter-tokens always have:
     * name, which is the name of the filter
     *
     * @return \Generator
     */
    protected function scanFilter()
    {

        foreach ($this->scanToken('filter', ':(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*)') as $token) {

            yield $token;

            foreach ($this->scanTextBlock() as $subToken)
                yield $subToken;
        }
    }

    /**
     * Scans for imports and yields an <import>-token if found.
     *
     * Import-tokens always have:
     * importType, which is either "extends" or "include
     * path, the (relative) path to which the import points
     *
     * Import-tokens may have:
     * filter, which is an optional filter that should be only
     *         usable on "include"
     *
     * @return \Generator
     */
    protected function scanImport()
    {

        return $this->scanToken(
            'import',
            '(?<importType>extends|include)(?::(?<filter>[a-zA-Z_][a-zA-Z0-9\-_]*))?[\t ]+(?<path>[a-zA-Z0-9\-_\\/\. ]+)'
        );
    }

    /**
     * Scans for <block>-tokens.
     *
     * Blocks can have three styles:
     * block append|prepend|replace name
     * append|prepend|replace name
     * or simply
     * block (for mixin blocks)
     *
     * Block-tokens may have:
     * mode, which is either "append", "prepend" or "replace"
     * name, which is the name of the block
     *
     * @return \Generator
     */
    protected function scanBlock()
    {

        foreach ($this->scanToken(
            'block',
            'block((?:[\t ]+(?<mode>append|prepend|replace))?(?:[\t ]+(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*))|[\t ]*\n)'
        ) as $token) {

            yield $token;

            //Allow direct content via <sub> token (should do <indent> in the parser)
            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }

        foreach ($this->scanToken(
            'block',
            '(?<mode>append|prepend|replace)(?:[\t ]+(?<name>[a-zA-ZA-Z][a-zA-Z0-9\-_]*))'
        ) as $token) {

            yield $token;

            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
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
     * @todo Avoid block parsing on <do>-loops
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
     * Scans for !=-style expression.
     *
     * e.g.
     * != expr
     * = expr
     *
     * Expression-tokens always have:
     * escaped, which indicates that the expression result should be escaped
     * value, which is the code of the expression
     *
     * @return \Generator
     */
    protected function scanExpression()
    {

        foreach ($this->scanToken(
            'expression',
            "([?]?[!]?[=])[\t ]*(?<value>[^\n]*)"
        ) as $token) {

            $token['escaped'] = strstr($this->getMatch(1), '!') ? false : true;
            $token['unchecked'] = strstr($this->getMatch(1), '?') ? true : false;
            yield $token;
        }
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
     * Scans for a <expansion>-token.
     *
     * (a: b-style expansion or a:b-style tags)
     *
     * Expansion-tokens always have:
     * withSpace, which indicates wether there's a space after the double-colon
     *
     * Usually, if there's no space, it should be handled as part of a tag-name
     *
     * @return \Generator
     */
    protected function scanExpansion()
    {

        if ($this->peek() === ':') {

            $this->consume();
            $token = $this->createToken('expansion');

            $spaces = $this->readSpaces();
            $token['withSpace'] = !empty($spaces);

            yield $token;
        }
    }

    /**
     * Scans sub-expressions of elements, e.g. a text-block
     * initiated with a dot (.) or a block expansion.
     *
     * Yields whatever scanTextBlock() and scanExpansion() yield
     *
     * @return \Generator
     */
    protected function scanSub()
    {

        //Escaped text after
        if ($this->peek(2) === '! ') {

            $this->consume();

            foreach ($this->scanText(true) as $token)
                yield $token;
        }

        if ($this->match('([!]?)\.')) {

            $this->consumeMatch();

            foreach ($this->scanTextBlock($this->getMatch(1) === '!') as $token)
                yield $token;
        }

        foreach ($this->scanExpansion() as $token)
            yield $token;
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

        foreach ($this->scanToken('tag', '(?<name>(([a-zA-Z_][a-zA-Z0-9\-_]*)?[\?!#]\{[^\}]+\}([a-zA-Z_][a-zA-Z0-9\-_]*)?|[a-zA-Z_][a-zA-Z0-9\-_]*))', 'i') as $token) {

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
                $spaces = null;

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
                        $spaces = $this->read('ctype_space');
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
                    $spaces = null;
                }

                //Check escaping flag (!) if a name is given.
                //Avoids escaping when you call e.g.
                //+btn(!$someCondition)
                if ($token['name'] && $char === '!') {

                    $token['escaped'] = false;
                    $this->consume();
                    $char = $this->peek();
                    $spaces = null;
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
                    $spaces = null;
                }

                yield $token;

                if (!empty($spaces)) {

                    $continue = true;
                    continue;
                }

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

        $near = $this->isAtEnd() ? 'END' : $this->peek(10);
        $message = "Failed to lex jade: $message (Line: {$this->line}, Offset: {$this->offset}, Near: `$near`)";
        throw new Exception($message);
    }

    /**
     * mb_* compatible version of PHP's strlen.
     *
     * (so we don't require mb.func_overload)
     *
     * @see strlen
     * @see mb_strlen
     *
     * @param string $string the string to get the length of
     *
     * @return int the multi-byte-respecting length of the string
     */
    protected function strlen($string)
    {

        if (function_exists('mb_strlen'))
            return mb_strlen($string, $this->options['encoding']);

        return strlen($string);
    }

    /**
     * mb_* compatible version of PHP's strpos.
     *
     * (so we don't require mb.func_overload)
     *
     * @see strpos
     * @see mb_strpos
     *
     * @param string   $haystack the string to search in
     * @param string   $needle   the string we search for
     * @param int|null $offset   the offset at which we might expect it
     *
     * @return int|false the offset of the string or false, if not found
     */
    protected function strpos($haystack, $needle, $offset = null)
    {

        if (function_exists('mb_strpos'))
            return mb_strpos($haystack, $needle, $offset, $this->options['encoding']);

        return strpos($haystack, $needle, $offset);
    }

    /**
     * mb_* compatible version of PHP's substr.
     *
     * (so we don't require mb.func_overload)
     *
     * @see substr
     * @see mb_substr
     *
     * @param string   $string the string to get a sub-string of
     * @param int      $start  the start-index
     * @param int|null $range  the amount of characters we want to get
     *
     * @return string the sub-string
     */
    protected function substr($string, $start, $range = null)
    {

        if (function_exists('mb_substr'))
            return mb_substr($string, $start, $range, $this->options['encoding']);

        return substr($string, $start, $range);
    }

    /**
     * mb_* compatible version of PHP's substr_count.
     *
     * (so we don't require mb.func_overload)
     *
     * @see substr_count
     * @see mb_substr_count
     *
     * @param string $haystack the string we want to count sub-strings in
     * @param string $needle   the sub-string we want to count inside $haystack
     *
     * @return int the amount of occurences of $needle in $haystack
     */
    protected function substr_count($haystack, $needle)
    {

        if (function_exists('mb_substr_count'))
            return mb_substr_count($haystack, $needle, $this->options['encoding']);

        return substr_count($haystack, $needle);
    }
}