<?php

namespace Tale\Jade;

use Tale\Jade\Lexer\Exception;
use Tale\Jade\Lexer\Token;

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

    public function lex($input)
    {

        $this->_input = $input;
        $this->_length = $this->strlen($this->_input);
        $this->_position = 0;

        $this->_line = 0;
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
            'textNode',
            'textLine'
        ], true) as $token)
            yield $token;
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

            $newLines = $this->substr_count($this->_lastPeekResult, "\n");
            $this->_line += $newLines;

            if ($newLines) {

                $parts = explode("\n", $this->_lastPeekResult);
                $this->_offset = strlen($parts[count($parts) - 1]) - 1;
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

        return $this->consume($this->strlen($this->_lastMatches[0]));
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

        $className = __NAMESPACE__.'\\Lexer\\Token\\'.ucfirst($type).'Token';
        return new $className($this);
    }

    protected function scanToken($type, $pattern, $modifiers = '')
    {

        if (!$this->match($pattern, $modifiers))
            return;

        $this->consumeMatch();
        $token = $this->createToken($type);
        foreach ($this->_lastMatches as $key => $value) {

            if (is_string($key) && !empty($value)) {

                $method = 'set'.ucfirst($key);

                if (method_exists($token, $method))
                    $token->{$method}($value);
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
        $token = $levels > 0
               ? $this->createToken('indent')
               : $this->createToken('outdent');

        $token->setLevels(abs($levels));

        yield $token;
    }

    protected function scanNewLine()
    {

        foreach ($this->scanToken('newLine', "^[\r]?\n") as $token) {

            $this->_line++;
            $this->_offset = 0;
            yield $token;
        }
    }

    protected function scanTextLine()
    {

        foreach ($this->scanToken('text', "([^\r\n]*)") as $token) {

            $value = trim($this->getMatch(1));

            if (empty($value))
                continue;

            $token->setValue($value);
            yield $token;
        }
    }


    protected function scanTextBlock()
    {

        foreach ($this->scanTextLine() as $token)
            yield $token;

        foreach ($this->scanFor(['newLine', 'indent']) as $token) {

            yield $token;

            if ($token instanceof Token\IndentToken) {

                $level = 0;
                foreach ($this->scanFor(['indent', 'newLine', 'textLine']) as $subToken) {

                    yield $subToken;

                    if ($subToken instanceof Token\IndentToken)
                        $level += $subToken->getLevels();

                    if ($subToken instanceof Token\OutdentToken) {

                        $level -= $subToken->getLevels();

                        if ($level <= 0)
                            break 2;
                    }
                }
            }
        }
    }

    protected function scanTextNode()
    {

        if ($this->peek() !== '|')
            return;

        $this->consume();
        foreach ($this->scanTextBlock() as $token)
            yield $token;
    }


    protected function scanComment()
    {

        if (!$this->match('\/\/(-)?'))
            return;

        $this->consumeMatch();

        /** @var \Tale\Jade\Lexer\Token\CommentToken $token */
        $token = $this->createToken('comment');

        if ($this->getMatch(1))
            $token->unrender();

        yield $token;

        foreach ($this->scanTextBlock() as $token)
            yield $token;
    }

    protected function scanFilter()
    {

        foreach ($this->scanToken('filter', ':(?<filterName>[a-zA-Z][a-zA-Z0-9\-_]*)?') as $token) {

            yield $token;

            foreach ($this->scanTextBlock() as $subToken)
                yield $subToken;
        }
    }

    protected function scanImport()
    {

        return $this->scanToken(
            'import',
            '(?<type>extends|include)(?::(?<filter>[a-zA-Z][a-zA-Z0-9\-_]*))?\s+(?<path>[a-zA-Z0-9\-_\\/\. ]+)'
        );
    }

    protected function scanBlock()
    {

        foreach ($this->scanToken(
            'block',
            'block(?:\s+(?<type>append|prepend|replace))?(?:\s+(?<name>[a-zA-Z][a-zA-Z0-9\-_]*))?'
        ) as $token) {

            yield $token;

            //Allow direct content via <sub> token (should do <indent> in the parser)
            foreach ($this->scanSub() as $subToken)
                yield $subToken;
        }

        //TODO: Doing this twice seems like a DRY-fail, fix this
        foreach ($this->scanToken(
            'block',
            '(?<type>append|prepend|replace)(?:\s+(?<name>[a-zA-ZA-Z][a-zA-Z0-9\-_]*))'
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
            'case'
        ) as $token) {

            yield $token;

            foreach ($this->scanTextLine() as $subToken)
                yield $subToken;
        }
    }

    protected function scanWhen()
    {

        foreach ($this->scanToken(
            'when',
            '(when|default)'
        ) as $token) {

            $type = $this->getMatch(1);

            if ($type === 'default')
                $token->setDefault();

            yield $token;

            if ($this->getMatch(1) === 'when')
                foreach ($this->scanTextLine() as $subToken)
                    yield $subToken;
        }
    }

    protected function scanConditional()
    {

        foreach ($this->scanToken(
            'conditional',
            '(?<type>if|unless|else( ?if)?)\s+'
        ) as $token) {

            yield $token;

            if ($this->getMatch(1) !== 'else')
                foreach ($this->scanTextLine() as $subToken)
                    yield $subToken;
        }
    }

    protected function scanEach()
    {

        foreach ($this->scanToken(
            'each',
            'each\s+\$(?<itemName>[a-zA-Z][a-zA-Z0-9\-_]*)(?:\s*,\s*\$(?<keyName>[a-zA-Z][a-zA-Z0-9\-_]*))\s+in\s+'
        ) as $token) {

            yield $token;

            foreach ($this->scanTextLine() as $subToken)
                yield $subToken;
        }
    }

    protected function scanWhile()
    {

        foreach ($this->scanToken(
            'while',
            'while\s+'
        ) as $token) {

            yield $token;

            foreach ($this->scanTextLine() as $subToken)
                yield $subToken;
        }
    }

    protected function scanDo()
    {

        foreach ($this->scanToken(
            'do',
            'do\s'
        ) as $token) {

            yield $token;
        }
    }

    protected function scanExpression()
    {

        if ($this->peek() === '-') {

            $this->consume();
            yield $this->createToken('expression');

            foreach ($this->scanTextBlock() as $subToken)
                yield $subToken;
        }

        foreach ($this->scanToken(
            'expression',
            '[!]?[=]'
        ) as $token) {

            yield $token;

            if ($this->getMatch(0) === '!=')
                $token->escape();

            foreach ($this->scanTextLine() as $subToken)
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

        return $this->scanToken('doctype', "(doctype|!!!) (?<type>[^\r\n]*)");
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

        foreach ($this->scanToken('mixin', 'mixin\s+(?<name>[a-zA-Z][a-zA-Z0-9\-_]*)') as $token) {

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
        $this->readSpaces();

        if ($this->peek() !== ')') {

            $continue = true;
            while(!$this->isAtEnd() && $continue) {

                /** @var \Tale\Jade\Lexer\Token\AttributeToken $token */
                $token = $this->createToken('attribute');
                $name = null;
                $value = null;

                if ($this->match('([a-zA-Z][a-zA-Z0-9\-_]*)', 'i')) {

                    $this->consumeMatch();
                    $name = $this->getMatch(1);
                    $token->setName($name);
                    $this->readSpaces();
                }

                if ($this->peek() === '!') {

                    $token->escape();
                    $this->consume();
                }

                if (!$name || $this->peek() === '=') {

                    if ($name) {

                        $this->consume();
                        $this->readSpaces();
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

                    $token->setValue($value);
                }

                if ($this->peek() === ',') {

                    $this->consume();
                    $this->readSpaces();
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
        throw new Exception($message);
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