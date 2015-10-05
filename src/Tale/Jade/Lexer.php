<?php

namespace Tale\Jade;

/**
 * The Lexer parses the input string into tokens
 * that can be worked with easier
 *
 * @package Tale\Jade
 */
class Lexer
{

    /**
     * Tab Indentation (\t)
     */
    const INDENT_TAB = "\t";
    /**
     * Space Indentation ( )
     */
    const INDENT_SPACE = ' ';

    /**
     * The current input string
     *
     * @var string
     */
    private $_input;

    /**
     * The total length of the current input
     *
     * @var int
     */
    private $_length;

    /**
     * The current position inside the input string
     *
     * @var int
     */
    private $_position;

    /**
     * The current line we are on
     *
     * @var int
     */
    private $_line;

    /**
     * The current offset in a line we are on
     * Resets on each new line and increases on each read character
     *
     * @var int
     */
    private $_offset;

    /**
     * The current indentation level we are on
     *
     * @var int
     */
    private $_level;

    /**
     * The current indentation character
     *
     * @var string
     */
    private $_indentStyle;

    /**
     * The width of the indentation, meaning how often $_indentStyle
     * is repeated for each $_level
     *
     * @var string
     */
    private $_indentWidth;

    /**
     * The last result gotten via ->peek()
     *
     * @var string
     */
    private $_lastPeekResult;

    /**
     * The last matches gotten via ->match()
     *
     * @var array
     */
    private $_lastMatches;

    /**
     * Creates a new lexer instance
     * The options should be an associative array
     *
     * Valid options are:
     *      indentStyle: The indentation character
     *      indentWidth: How often to repeat indentStyle
     *      encoding: The encoding when working with mb_*-functions
     *
     * @param array|null $options
     *
     * @throws \Exception
     */
    public function __construct(array $options = null)
    {

        $this->_options = array_replace([
            'indentStyle' => null,
            'indentWidth' => null,
            'encoding' => mb_internal_encoding()
        ], $options ? $options : []);

        //Validate options
        if (!in_array($this->_options['indentStyle'], [null, self::INDENT_TAB, self::INDENT_SPACE]))
            throw new \Exception(
                "indentStyle needs to be null or one of the INDENT_* constants of the lexer"
            );

        if (!is_null($this->_options['indentWidth']) &&
            (!is_int($this->_options['indentWidth']) || $this->_options['indentWidth'] < 1)
        )
            throw new \Exception(
                "indentWidth needs to be a integer above 0"
            );
    }

    /**
     * Returns the current input worked on
     *
     * @return string
     */
    public function getInput()
    {
        return $this->_input;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->_length;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->_position;
    }

    /**
     * @return int
     */
    public function getLine()
    {
        return $this->_line;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->_offset;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->_level;
    }

    /**
     * @return string
     */
    public function getIndentStyle()
    {
        return $this->_indentStyle;
    }

    /**
     * @return int
     */
    public function getIndentWidth()
    {
        return $this->_indentWidth;
    }

    /**
     * @return string|null
     */
    public function getLastPeekResult()
    {
        return $this->_lastPeekResult;
    }

    /**
     * @return array|null
     */
    public function getLastMatches()
    {
        return $this->_lastMatches;
    }

    /**
     * @param $input
     *
     * @return \Generator
     */
    public function lex($input)
    {

        $this->_input = rtrim(str_replace([
            "\r", "\x00"
        ], '', $input))."\n";
        $this->_length = $this->strlen($this->_input);
        $this->_position = 0;

        $this->_line = 1;
        $this->_offset = 0;
        $this->_level = 0;

        $this->_indentStyle = $this->_options['indentStyle'];
        $this->_indentWidth = $this->_options['indentWidth'];

        $this->_lastPeekResult = null;
        $this->_lastMatches = null;

        foreach ($this->scanFor([
            'newLine', 'indent',
            'import',
            'block',
            'conditional', 'each', 'case', 'when', 'do', 'while',
            'mixin', 'mixinCall',
            'doctype',
            'tag', 'classes', 'id', 'attributes',
            'assignment',
            'comment', 'filter',
            'expression',
            'markup',
            'textLine',
            'text'
        ], true) as $token)
            yield $token;
    }

    /**
     * @param $input
     */
    public function dump($input)
    {

        foreach ($this->lex($input) as $token) {

            $type = $token['type'];
            $line = $token['line'];
            $offset = $token['offset'];
            unset($token['type'], $token['line'], $token['offset']);

            echo "[$type($line:$offset)";
            $vals = implode(', ', array_map(function($key, $value) {

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
     * @return bool
     */
    protected function isAtEnd()
    {

        return $this->_position >= $this->_length;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    protected function peek($length = 1)
    {

        $this->_lastPeekResult = $this->substr($this->_input, 0, $length);
        return $this->_lastPeekResult;
    }

    /**
     * @param null $length
     *
     * @return $this
     * @throws \Tale\Jade\LexException
     */
    protected function consume($length = null)
    {

        if ($length === null) {

            if ($this->_lastPeekResult === null)
                $this->throwException(
                    "Failed to consume: Nothing has been peeked and you"
                    ." didnt pass a length to consume"
                );

            $length = $this->strlen($this->_lastPeekResult);
        }

        $this->_input = $this->substr($this->_input, $length);
        $this->_position += $length;
        $this->_offset += $length;
        return $this;
    }

    /**
     * @param     $callback
     * @param int $length
     *
     * @return string
     * @throws \Exception
     */
    protected function read($callback, $length = 1)
    {

        if (!is_callable($callback))
            throw new \Exception(
                "Argument 1 passed to peekWhile needs to be callback"
            );

        $result = '';
        while (!$this->isAtEnd() && $callback($this->peek($length)))
        {

            //Keep $_line and $_offset updated
            $newLines = $this->substr_count($this->_lastPeekResult, "\n");
            $this->_line += $newLines;

            if ($newLines) {

                if (strlen($this->_lastPeekResult) === 1)
                    $this->_offset = 0;
                else {

                    $parts = explode("\n", $this->_lastPeekResult);
                    $this->_offset = strlen($parts[count($parts) - 1]) - 1;
                }
            }

            $this->consume();
            $result .= $this->_lastPeekResult;
        }

        return $result;
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function readSpaces()
    {

        return $this->read(function($char) {

            return $char === self::INDENT_SPACE || $char === self::INDENT_TAB;
        });
    }

    /**
     * @param array|null $breakChars
     *
     * @return string
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
        $break = false;
        while (!$this->isAtEnd() && !$break) {

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

                        $break = true;
                        break;
                    }

                    $level--;
                    break;
            }

            if (in_array($char, $breakChars, true) && !$inString && $level === 0)
                $break = true;

            if (!$break) {

                $value .= $char;
                $this->consume();
            }
        }

        return trim($value);
    }

    /**
     * @param        $pattern
     * @param string $modifiers
     *
     * @return int
     */
    protected function match($pattern, $modifiers = '')
    {

        return preg_match(
            "/^$pattern/$modifiers",
            $this->_input,
            $this->_lastMatches
        );
    }

    /**
     * @return \Tale\Jade\Lexer
     */
    protected function consumeMatch()
    {

        //Make sure we don't consume matched newlines (We match for them sometimes)
        //We need the newLine tokens and don't want them consumed here.
        $match = $this->_lastMatches[0] !== "\n" ? rtrim($this->_lastMatches[0], "\n") : $this->_lastMatches[0];
        return $this->consume($this->strlen($match));
    }

    /**
     * @param $index
     *
     * @return null
     */
    protected function getMatch($index)
    {

        return isset($this->_lastMatches[$index]) ? $this->_lastMatches[$index] : null;
    }

    /**
     * @param array      $scans
     * @param bool|false $throwException
     *
     * @return \Generator|void
     * @throws \Tale\Jade\LexException
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
                    'Unexpected `'.htmlentities($this->peek(20), ENT_QUOTES).'`, '
                    .implode(', ', $scans).' expected'
                );
            } else
                return;
        }
    }

    /**
     * @param $type
     *
     * @return array
     */
    protected function createToken($type)
    {

        return [
            'type' => $type,
            'line' => $this->_line,
            'offset' => $this->_offset
        ];
    }

    /**
     * @param        $type
     * @param        $pattern
     * @param string $modifiers
     *
     * @return \Generator|void
     */
    protected function scanToken($type, $pattern, $modifiers = '')
    {

        if (!$this->match($pattern, $modifiers))
            return;

        $this->consumeMatch();
        $token = $this->createToken($type);
        foreach ($this->_lastMatches as $key => $value) {

            //We append all STRING-Matches (?<name>) to the token
            if (is_string($key)) {

                $token[$key] = empty($value) ? null : $value;
            }
        }

        yield $token;
    }

    /**
     * @return \Generator|void
     * @throws \Tale\Jade\LexException
     */
    protected function scanIndent()
    {

        if ($this->_offset !== 0 || !$this->match("([\t ]*)"))
            return;

        $this->consumeMatch();
        $indent = $this->getMatch(1);

        //If this is an empty line, we ignore the indentation completely.
        foreach ($this->scanNewLine() as $token) {

            yield $token;
            return;
        }

        $oldLevel = $this->_level;
        if (!empty($indent)) {

            $spaces = $this->strpos($indent, ' ') !== false;
            $tabs = $this->strpos($indent, "\t") !== false;
            $mixed = $spaces && $tabs;

            if ($mixed)
                $this->throwException(
                    "Mixed indentation style encountered. "
                    ."Dont mix tabs and spaces. Stick to one of both."
                );

            $indentStyle = $tabs ? self::INDENT_TAB : self::INDENT_SPACE;
            if ($this->_indentStyle && $this->_indentStyle !== $indentStyle)
                $this->throwException(
                    "Mixed indentation style encountered. "
                    ."You used another indentation style in this line than in "
                    ."previous lines. Dont do that."
                );

            if (!$this->_indentWidth)
                //We will use the pretty first indentation as our indent width
                $this->_indentWidth = $this->strlen($indent);

            $this->_level = intval(round($this->strlen($indent) / $this->_indentWidth));

            if ($this->_level > $oldLevel + 1)
                $this->throwException(
                    "You should indent in by one level only"
                );
        } else
            $this->_level = 0;

        $levels = $this->_level - $oldLevel;

        if (!empty($indent) && $levels === 0) {

            yield $this->createToken('nodent');
            return;
        }

        //We create a token for each indentation/outdentation
        $type = $levels > 0 ? 'indent' : 'outdent';
        $levels = abs($levels);

        while ($levels--)
            yield $this->createToken($type);
    }

    /**
     * @return \Generator
     */
    protected function scanNewLine()
    {

        foreach ($this->scanToken('newLine', "\n") as $token) {

            $this->_line++;
            $this->_offset = 0;
            yield $token;
        }
    }

    /**
     * @return \Generator
     */
    protected function scanText()
    {

        foreach ($this->scanToken('text', "([^\n]*)") as $token) {

            $value = trim($this->getMatch(1));

            if (empty($value))
                continue;

            $token['value'] = $value;
            yield $token;
        }
    }


    /**
     * @return \Generator
     */
    protected function scanTextBlock()
    {

        foreach ($this->scanText() as $token)
            yield $token;

        foreach ($this->scanFor(['newLine', 'indent']) as $token) {

            yield $token;

            if ($token['type'] === 'indent') {

                $level = 1;
                foreach ($this->scanFor(['indent', 'newLine', 'text']) as $subToken) {

                    yield $subToken;

                    if ($subToken['type'] === 'indent')
                        $level++;

                    if ($subToken['type'] === 'outdent') {

                        $level--;

                        if ($level <= 0)
                            break 2;
                    }
                }
            }
        }
    }

    /**
     * @return \Generator|void
     */
    protected function scanTextLine()
    {

        if ($this->peek() !== '|')
            return;

        $this->consume();
        foreach ($this->scanTextBlock() as $token)
            yield $token;
    }

    /**
     * @return \Generator|void
     */
    protected function scanMarkup()
    {

        if ($this->peek() !== '<')
            return;

        foreach ($this->scanText() as $token)
            yield $token;
    }

    /**
     * @return \Generator|void
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
     * @return \Generator
     */
    protected function scanFilter()
    {

        foreach ($this->scanToken('filter', ':(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*)?') as $token) {

            yield $token;

            foreach ($this->scanTextBlock() as $subToken)
                yield $subToken;
        }
    }

    /**
     * @return \Generator|void
     */
    protected function scanImport()
    {

        return $this->scanToken(
            'import',
            '(?<importType>extends|include)(?::(?<filter>[a-zA-Z_][a-zA-Z0-9\-_]*))?[\t ]+(?<path>[a-zA-Z0-9\-_\\/\. ]+)'
        );
    }

    /**
     * @return \Generator
     */
    protected function scanBlock()
    {

        foreach ($this->scanToken(
            'block',
            '(?J:block(?:[\t ]+(?<mode>append|prepend|replace))?(?:[\t ]+(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*))?|(?<mode>append|prepend|replace)(?:[\t ]+(?<name>[a-zA-ZA-Z][a-zA-Z0-9\-_]*)))'
        ) as $token) {

            yield $token;

            //Allow direct content via <sub> token (should do <indent> in the parser)
            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
    }

    /**
     * @return \Generator
     */
    protected function scanCase()
    {

        return $this->scanControlStatement('case', ['case']);
    }

    /**
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
     * @return \Generator
     */
    protected function scanConditional()
    {

        return $this->scanControlStatement('conditional', [
            'if', 'unless', 'elseif', 'else if', 'else'
        ], 'conditionType');
    }

    /**
     * @param       $type
     * @param array $names
     * @param null  $nameAttribute
     *
     * @return \Generator
     * @throws \Tale\Jade\LexException
     */
    protected function scanControlStatement($type, array $names, $nameAttribute = null)
    {

        foreach ($names as $name) {

            if (!$this->match("{$name}[\t \n]"))
                continue;

            $this->consumeMatch();
            $this->readSpaces();

            $token = $this->createToken($type);
            if ($nameAttribute)
                $token[$nameAttribute] = str_replace(' ', '', $name);
            $token['subject'] = null;

            //each is a special little unicorn
            if ($name === 'each') {

                if (!$this->match('\$?(?<itemName>[a-zA-Z_][a-zA-Z0-9_]*)(?:[\t ]*,[\t ]*\$?(?<keyName>[a-zA-Z_][a-zA-Z0-9_]*))?[\t ]+in[\t ]+'))
                    $this->throwException(
                        "The syntax for each is `each [$]itemName[, [$]keyName] in [subject]`",
                        $token
                    );

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
            } elseif ($this->match("([^:\n]+)")){

                $this->consumeMatch();
                $token['subject'] = trim($this->getMatch(1));
            }

            yield $token;

            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
    }

    /**
     * @return \Generator
     */
    protected function scanEach()
    {

        return $this->scanControlStatement('each', ['each']);
    }

    /**
     * @return \Generator
     */
    protected function scanWhile()
    {

        return $this->scanControlStatement('while', ['while']);
    }

    /**
     * @return \Generator
     */
    protected function scanDo()
    {

        return $this->scanControlStatement('do', ['do']);
    }

    /**
     * @return \Generator
     */
    protected function scanExpression()
    {

        if ($this->peek() === '-') {

            $this->consume();
            $token = $this->createToken('expression');
            $token['escaped'] = false;
            $token['return'] = false;
            yield $token;
            $this->readSpaces();

            foreach ($this->scanTextBlock() as $subToken)
                yield $subToken;
        }

        foreach ($this->scanToken(
            'expression',
            "([!]?[=])[\t ]*"
        ) as $token) {

            $token['escaped'] = $this->getMatch(1) === '!=' ? false : true;
            $token['return'] = true;
            yield $token;

            foreach ($this->scanText() as $subToken)
                yield $subToken;
        }
    }

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
     * @return \Generator
     */
    protected function scanSub()
    {

        foreach ($this->scanExpansion() as $token)
            yield $token;

        if ($this->peek() === '.') {

            $this->consume();
            foreach ($this->scanTextBlock() as $token)
                yield $token;
        }
    }

    /**
     * @return \Generator|void
     */
    protected function scanDoctype()
    {

        return $this->scanToken('doctype', "(doctype|!!!) (?<name>[^\n]*)");
    }

    /**
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
     * @return \Generator
     */
    protected function scanClasses()
    {

        foreach ($this->scanToken('class', '(\.(?<name>[a-zA-Z_][a-zA-Z0-9\-_]+))', 'i') as $token) {

            yield $token;

            //Make sure classes are scanned on this before we scan the . add-on
            foreach ($this->scanClasses() as $subToken)
                yield $subToken;

            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
    }

    /**
     * @return \Generator
     */
    protected function scanId()
    {

        foreach ($this->scanToken('id', '(#(?<name>[a-zA-Z_][a-zA-Z0-9\-_]+))', 'i') as $token) {

            yield $token;

            //Make sure classes are scanned on this before we scan the . add-on
            foreach ($this->scanClasses() as $subToken)
                yield $subToken;

            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
    }

    /**
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
     * @return \Generator
     */
    protected function scanAssignment()
    {

        foreach ($this->scanToken('assignment', '&(?<name>[a-zA-Z_][a-zA-Z0-9\-_]*)') as $token) {

            yield $token;
        }
    }

    /**
     * @return \Generator|void
     * @throws \Exception
     * @throws \Tale\Jade\LexException
     */
    protected function scanAttributes()
    {

        if ($this->peek() !== '(')
            return;

        $this->consume();
        yield $this->createToken('attributeStart');
        $this->read('ctype_space');

        if ($this->peek() !== ')') {

            $continue = true;
            while(!$this->isAtEnd() && $continue) {

                $token = $this->createToken('attribute');
                $token['name'] = null;
                $token['value'] = null;
                $token['escaped'] = true;

                if ($this->match('((\.\.\.)?[a-zA-Z_][a-zA-Z0-9\-_]*)', 'i')) {

                    $this->consumeMatch();
                    $token['name'] = $this->getMatch(1);
                    $this->read('ctype_space');
                }

                if ($this->peek() === '!') {

                    $token['escaped'] = false;
                    $this->consume();
                }

                if (!$token['name'] || $this->peek() === '=') {

                    if ($token['name']) {

                        $this->consume();
                        $this->read('ctype_space');
                    }

                    $token['value'] = $this->readBracketContents([',']);
                }

                if ($this->peek() === ',') {

                    $this->consume();
                    $this->read('ctype_space');
                    $continue = true;
                } else {

                    $continue = false;
                }

                yield $token;
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

        foreach($this->scanSub() as $token)
            yield $token;
    }

    /**
     * @param $message
     *
     * @throws \Tale\Jade\LexException
     */
    protected function throwException($message)
    {

        $message = "Failed to parse jade: $message (Line: {$this->_line}, Offset: {$this->_offset})";
        throw new LexException($message);
    }

    /**
     * @param $string
     *
     * @return int
     */
    protected function strlen($string)
    {

        if (function_exists('mb_strlen'))
            return mb_strlen($string, $this->_options['encoding']);

        return strlen($string);
    }

    /**
     * @param      $haystack
     * @param      $needle
     * @param null $offset
     *
     * @return bool|int
     */
    protected function strpos($haystack, $needle, $offset = null)
    {

        if (function_exists('mb_strpos'))
            return mb_strpos($haystack, $needle, $offset, $this->_options['encoding']);

        return strpos($haystack, $needle, $offset);
    }

    /**
     * @param      $string
     * @param      $start
     * @param null $range
     *
     * @return string
     */
    protected function substr($string, $start, $range = null)
    {

        if (function_exists('mb_substr'))
            return mb_substr($string, $start, $range, $this->_options['encoding']);

        return substr($string, $start, $range);
    }

    /**
     * @param $haystack
     * @param $needle
     *
     * @return int
     */
    protected function substr_count($haystack, $needle)
    {
        if (function_exists('mb_substr_count'))
            return mb_substr_count($haystack, $needle, $this->_options['encoding']);

        return substr_count($haystack, $needle);
    }
}