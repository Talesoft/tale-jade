<?php

namespace Tale\Jade;

class Lexer
{

    const INDENT_TAB = "\t";
    const INDENT_SPACE = ' ';

    private $_input;

    private $_length;
    private $_position;
    private $_line;
    private $_offset;
    private $_level;

    private $_indentStyle;
    private $_indentWidth;

    private $_lastPeekResult;
    private $_lastMatches;

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

        $this->_input = str_replace([
            "\r", "\x00"
        ], '', $input);
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
            'textLine',
            'text'
        ], true) as $token)
            yield $token;
    }

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

    protected function isAtEnd()
    {

        return $this->_position >= $this->_length;
    }

    protected function peek($length = 1)
    {

        $this->_lastPeekResult = $this->substr($this->_input, 0, $length);
        return $this->_lastPeekResult;
    }

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

    protected function readSpaces()
    {

        return $this->read(function($char) {

            return $char === self::INDENT_SPACE || $char === self::INDENT_TAB;
        });
    }

    protected function match($pattern, $modifiers = '')
    {

        return preg_match(
            "/^$pattern/$modifiers",
            $this->_input,
            $this->_lastMatches
        );
    }

    protected function consumeMatch()
    {

        //Make sure we don't consume matched newlines (We match for them sometimes)
        //We need the newLine tokens and don't want them consumed here.
        $match = $this->_lastMatches[0] !== "\n" ? rtrim($this->_lastMatches[0], "\n") : $this->_lastMatches[0];
        return $this->consume($this->strlen($match));
    }

    protected function getMatch($index)
    {

        return isset($this->_lastMatches[$index]) ? $this->_lastMatches[$index] : null;
    }

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

    protected function createToken($type)
    {

        return [
            'type' => $type,
            'line' => $this->_line,
            'offset' => $this->_offset
        ];
    }

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

    protected function scanIndent()
    {

        if ($this->_offset !== 0 || !$this->match("([\t ]*)"))
            return;

        $this->consumeMatch();
        $indent = $this->getMatch(1);

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

            $this->_level = intval(ceil($this->strlen($indent) / $this->_indentWidth));
        } else
            $this->_level = 0;

        $levels = $this->_level - $oldLevel;

        if ($levels === 0)
            return;

        /** @var \Tale\Jade\Lexer\Token\IndentToken $token */
        $token = $this->createToken($levels > 0 ? 'indent' : 'outdent');

        $token['levels'] = abs($levels);

        yield $token;
    }

    protected function scanNewLine()
    {

        foreach ($this->scanToken('newLine', "\n") as $token) {

            $this->_line++;
            $this->_offset = 0;
            yield $token;
        }
    }

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


    protected function scanTextBlock()
    {

        foreach ($this->scanText() as $token)
            yield $token;

        foreach ($this->scanFor(['newLine', 'indent']) as $token) {

            yield $token;

            if ($token['type'] === 'indent') {

                $level = $token['levels'];
                foreach ($this->scanFor(['indent', 'newLine', 'text']) as $subToken) {

                    yield $subToken;

                    if ($subToken['type'] === 'indent')
                        $level += $subToken['levels'];

                    if ($subToken['type'] === 'outdent') {

                        $level -= $subToken['levels'];

                        if ($level <= 0)
                            break 2;
                    }
                }
            }
        }
    }

    protected function scanTextLine()
    {

        if ($this->peek() !== '|')
            return;

        $this->consume();
        foreach ($this->scanTextBlock() as $token)
            yield $token;
    }


    protected function scanComment()
    {

        if (!$this->match("\\/\\/(-)?[\t ]*"))
            return;

        $this->consumeMatch();

        $token = $this->createToken('comment');
        $token['rendered'] = $this->getMatch(1) ? true : false;

        yield $token;

        foreach ($this->scanTextBlock() as $token)
            yield $token;
    }

    protected function scanFilter()
    {

        foreach ($this->scanToken('filter', ':(?<name>[a-zA-Z][a-zA-Z0-9\-_]*)?') as $token) {

            yield $token;

            foreach ($this->scanTextBlock() as $subToken)
                yield $subToken;
        }
    }

    protected function scanImport()
    {

        return $this->scanToken(
            'import',
            '(?<importType>extends|include)(?::(?<filter>[a-zA-Z][a-zA-Z0-9\-_]*))?[\t ]+(?<path>[a-zA-Z0-9\-_\\/\. ]+)'
        );
    }

    protected function scanBlock()
    {

        foreach ($this->scanToken(
            'block',
            'block(?:[\t ]+(?<insertType>append|prepend|replace))?(?:[\t ]+(?<name>[a-zA-Z][a-zA-Z0-9\-_]*))?'
        ) as $token) {

            yield $token;

            //Allow direct content via <sub> token (should do <indent> in the parser)
            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }

        //TODO: Doing this twice seems like a DRY-fail, fix this
        foreach ($this->scanToken(
            'block',
            '(?<insertType>append|prepend|replace)(?:[\t ]+(?<name>[a-zA-ZA-Z][a-zA-Z0-9\-_]*))'
        ) as $token) {

            yield $token;

            //Allow direct content via <sub> token (should do <indent> in the parser)
            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
    }

    protected function scanCase()
    {

        foreach ($this->scanToken(
            'case',
            "case[\t ]+"
        ) as $token) {

            yield $token;

            foreach ($this->scanText() as $subToken)
                yield $subToken;
        }
    }

    protected function scanWhen()
    {

        foreach ($this->scanToken(
            'when',
            "(when[\t ]+|default[\t ]*\n)"
        ) as $token) {

            $type = $this->getMatch(1);

            if ($type === 'default')
                $token->setDefault();

            yield $token;

            if ($this->getMatch(1) === 'when')
                foreach ($this->scanText() as $subToken)
                    yield $subToken;
        }
    }

    protected function scanConditional()
    {

        foreach ($this->scanToken(
            'conditional',
            "(?<conditionType>(?:if|unless)[\t ]+|else[\t ]*\n|else([\t ]*if[\t ]+)?)"
        ) as $token) {

            $token['conditionType'] = trim($token['conditionType']);
            yield $token;

            if ($token['conditionType'] !== 'else')
                foreach ($this->scanText() as $subToken)
                    yield $subToken;
        }
    }

    protected function scanEach()
    {

        foreach ($this->scanToken(
            'each',
            "each[\t ]+[\$]?(?<itemName>[a-zA-Z][a-zA-Z0-9\-_]*)(?:[\t ]*,[\t ]*[\$]?(?<keyName>[a-zA-Z][a-zA-Z0-9\-_]*))[\t ]+in[\t ]+"
        ) as $token) {

            yield $token;

            foreach ($this->scanText() as $subToken)
                yield $subToken;
        }
    }

    protected function scanWhile()
    {

        foreach ($this->scanToken(
            'while',
            "while[\t ]*\n"
        ) as $token) {

            yield $token;

            foreach ($this->scanText() as $subToken)
                yield $subToken;
        }
    }

    protected function scanDo()
    {

        foreach ($this->scanToken(
            'do',
            "do[\t ]*\n"
        ) as $token) {

            yield $token;
        }
    }

    protected function scanExpression()
    {

        if ($this->peek() === '-') {

            $this->consume();
            yield $this->createToken('expression');
            $this->readSpaces();

            foreach ($this->scanTextBlock() as $subToken)
                yield $subToken;
        }

        foreach ($this->scanToken(
            'expression',
            "([!]?[=])[\t ]*"
        ) as $token) {

            $token['escaped'] = $this->getMatch(1) === '!=' ? false : true;
            yield $token;

            foreach ($this->scanText() as $subToken)
                yield $subToken;
        }
    }

    protected function scanSub()
    {

        if ($this->peek() === ':') {

            $this->consume();
            $token = $this->createToken('sub');

            $spaces = $this->readSpaces();

            if (!empty($spaces))
                yield $this->createToken('space');

            yield $token;
        }

        if ($this->peek() === '.') {

            $this->consume();
            foreach ($this->scanTextBlock() as $token)
                yield $token;
        }
    }

    protected function scanDoctype()
    {

        return $this->scanToken('doctype', "(doctype|!!!) (?<name>[^\n]*)");
    }

    protected function scanTag()
    {

        foreach ($this->scanToken('tag', '(?<name>[a-zA-Z][a-zA-Z0-9\-_]*)', 'i') as $token) {

            yield $token;

            //Make sure classes are scanned on this before we scan the . add-on
            foreach ($this->scanClasses() as $subToken)
                yield $subToken;

            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
    }

    protected function scanClasses()
    {

        foreach ($this->scanToken('class', '(\.(?<name>[a-zA-Z][a-zA-Z0-9\-_]+))', 'i') as $token) {

            yield $token;

            //Make sure classes are scanned on this before we scan the . add-on
            foreach ($this->scanClasses() as $subToken)
                yield $subToken;

            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
    }

    protected function scanId()
    {

        foreach ($this->scanToken('id', '(#(?<name>[a-zA-Z][a-zA-Z0-9\-_]+))', 'i') as $token) {

            yield $token;

            //Make sure classes are scanned on this before we scan the . add-on
            foreach ($this->scanClasses() as $subToken)
                yield $subToken;

            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
    }

    protected function scanMixin()
    {

        foreach ($this->scanToken('mixin', "mixin[\t ]+(?<name>[a-zA-Z][a-zA-Z0-9\-_]*)") as $token) {

            yield $token;

            //Make sure classes are scanned on this before we scan the . add-on
            foreach ($this->scanClasses() as $subToken)
                yield $subToken;

            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
    }

    protected function scanMixinCall()
    {

        foreach ($this->scanToken('mixinCall', '\+(?<name>[a-zA-Z][a-zA-Z0-9\-_]*)') as $token) {

            yield $token;

            //Make sure classes are scanned on this before we scan the . add-on
            foreach ($this->scanClasses() as $subToken)
                yield $subToken;

            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }
    }

    protected function scanAssignment()
    {

        foreach ($this->scanToken('assignment', '&(?<name>[a-zA-Z][a-zA-Z0-9\-_]*)') as $token) {

            yield $token;
        }
    }

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

                /** @var \Tale\Jade\Lexer\Token\AttributeToken $token */
                $token = $this->createToken('attribute');
                $token['name'] = null;
                $token['value'] = null;
                $token['escaped'] = true;

                if ($this->match('([a-zA-Z][a-zA-Z0-9\-_]*)', 'i')) {

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

                                if (!$inString)
                                    $level++;
                                break;
                            case ')':

                                if ($inString)
                                    break;

                                if ($level === 0) {

                                    $break = true;
                                    break;
                                }

                                $level--;
                                break;
                            case ',':

                                if (!$inString)
                                    $break = true;
                                break;
                        }

                        if (!$break) {

                            $value .= $char;
                            $this->consume();
                        }
                    }

                    $token['value'] = trim($value);
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

    protected function throwException($message)
    {

        $message = "Failed to parse jade: $message (Line: {$this->_line}, Offset: {$this->_offset})";
        throw new LexException($message);
    }

    protected function strlen($string)
    {

        if (function_exists('mb_strlen'))
            return mb_strlen($string, $this->_options['encoding']);

        return strlen($string);
    }

    protected function strpos($haystack, $needle, $offset = null)
    {

        if (function_exists('mb_strpos'))
            return mb_strpos($haystack, $needle, $offset, $this->_options['encoding']);

        return strpos($haystack, $needle, $offset);
    }

    protected function substr($string, $start, $range = null)
    {

        if (function_exists('mb_substr'))
            return mb_substr($string, $start, $range, $this->_options['encoding']);

        return substr($string, $start, $range);
    }

    protected function substr_count($haystack, $needle)
    {
        if (function_exists('mb_substr_count'))
            return mb_substr_count($haystack, $needle, $this->_options['encoding']);

        return substr_count($haystack, $needle);
    }
}