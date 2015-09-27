<?php

namespace Tale\Jade;


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
    private $_matches;

    public function __construct(array $options = null)
    {

        $this->_options = array_replace([
            'indentStyle' => null,
            'indentWidth' => null,
            'encoding' => mb_internal_encoding()
        ], $options ? $options : []);

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

        foreach ($this->await(
            'newLine', 'indent',
            'import',
            'block',
            'conditional', 'each', 'case', 'when', 'loop',
            'mixin', 'mixinCall',
            'doctype',
            'tag', 'classes', 'id', 'attributes',
            'merger',
            'comment', 'filter',
            'code',
            'text'
        ) as $token)
            yield $token;
    }

    public function dump($input)
    {

        echo '<pre>';
        echo $input;
        echo "\n\n";
        foreach ($this->lex($input) as $token) {

            echo "[$token->type";

            switch($token->type) {
                case 'indent':
                case 'outdent':
                    echo " $token->levels";
                    break;
                case 'extends':
                case 'include':

                    if ($token->filter)
                        echo " $token->filter";

                    echo " $token->path";
                    break;
                case 'tag':
                case 'class':
                case 'id':
                case 'filter':
                case 'mixin':
                case 'mixin-call':
                    echo " $token->name";
                    break;
                case 'block':
                    echo " $token->mode".($token->name ? " $token->name" : '');
                    break;
                case 'text':
                    echo ' '.str_replace("\n", '\n', $token->content);
                    break;
                case 'comment':
                case 'code':
                    echo $token->escaped ? ' escaped' : '';
                    break;
                case 'doctype':
                    echo " $token->value";
                    break;
                case 'case':
                case 'if':
                case 'elseif':
                case 'else':
                case 'unless':
                case 'each':
                case 'while':

                    if ($token->subject)
                        echo " $token->subject";

                    if ($token->type === 'each' && $token->itemName)
                        echo " $token->itemName";

                    if ($token->type === 'each' && $token->keyName)
                        echo " $token->keyName";
                    break;
                case 'when':
                    echo " $token->value";
                    break;
                case 'attribute':

                    if (isset($token->name))
                        echo " $token->name";

                    if ($token->value)
                        echo " $token->value";
                    break;
            }

            echo "]";

            if ($token->type === 'newLine')
                echo "\n";
        }
        echo '</pre>';
    }

    protected function createToken($type)
    {

        return new Token($type, $this->_line, $this->_offset);
    }

    protected function isAtEnd()
    {

        return $this->_position >= $this->_length;
    }

    protected function peek($length = 1)
    {

        return $this->substr($this->_input, 0, $length);
    }

    protected function consume($length = 1)
    {

        $this->_input = $this->substr($this->_input, $length);
        $this->_position += $length;
        $this->_offset += $length;
        return $this;
    }

    protected function peekWhile($callback, $length = 1, $reverse = false)
    {

        if (!is_callable($callback))
            throw new \Exception(
                "Argument 1 passed to peekWhile needs to be callback"
            );

        $token = '';
        while (!$this->isAtEnd()
            && ($reverse
                ? !$callback($string = $this->peek($length))
                : $callback($string = $this->peek($length))
            )
        )
        {


            $newLines = $this->substr_count($string, "\n");
            $this->_line += $newLines;

            if ($newLines) {

                $parts = explode("\n", $string);
                $this->_offset = strlen($parts[count($parts) - 1]) - 1;
            }

            $this->consume($length);
            $token .= $string;
        }

        return $token;
    }

    protected function skip()
    {

        $spaces = $this->peekWhile('ctype_space');
        return $spaces;
    }

    protected function peekUntil($callback, $length = 1)
    {

        return $this->peekWhile($callback, $length, true);
    }

    protected function match($pattern, $modifiers = '')
    {

        return preg_match(
            "/$pattern/$modifiers",
            $this->_input,
            $this->_matches
        );
    }

    protected function consumeMatch()
    {

        return $this->consume($this->strlen($this->_matches[0]));
    }

    protected function getMatch($index)
    {

        return isset($this->_matches[$index]) ? $this->_matches[$index] : null;
    }

    protected function scan(...$scans)
    {

        foreach ($scans as $scan) {

            foreach (call_user_func([$this, 'scan'.ucfirst($scan)]) as $token)
                yield $token;
        }
    }

    protected function await(...$scans)
    {

        while (!$this->isAtEnd()) {

            $found = false;
            foreach (call_user_func_array([$this, 'scan'], $scans) as $token) {

                yield $token;
                $found = true;
            }

            if (!$found)
                $this->throwException(implode(', ', $scans).' not found');
        }
    }

    protected function scanNewLine()
    {

        if ($this->peek() === "\r") {

            $this->consume();
        }

        if ($this->peek() === "\n") {

            $this->consume();
            $this->_line++;
            $this->_offset = 0;

            yield $this->createToken('newLine');
        }
    }

    protected function scanIndent()
    {

        if ($this->_offset !== 0 || !$this->match("^([\t ]*)"))
            return;

        $this->consumeMatch();
        $indent = $this->getMatch(1);

        foreach($this->scanNewLine() as $token) {

            yield $token;
            return;
        }

        var_dump("INDENT `$indent`");

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

        $token = $this->createToken($levels > 0 ? 'indent' : 'outdent');
        $token->levels = abs($levels);

        yield $token;
    }


    protected function scanText()
    {

        if (!$this->match("^([^\r\n]*)"))
            return;

        $this->consumeMatch();
        $text = $this->getMatch(1);

        if (!empty($text)) {

            $token = $this->createToken('text');
            $token->content = trim($text);
            yield $token;
        }
    }


    protected function scanTextBlock()
    {

        foreach ($this->await('newLine', 'indent', 'text') as $token) {

            yield $token;
            if ($token->type === 'indent') {

                $level = 0;
                foreach ($this->await('indent', 'newLine', 'text') as $subToken) {

                    yield $subToken;

                    if ($subToken->type === 'indent')
                        $level += $subToken->levels;

                    if ($subToken->type === 'outdent' && $level === 0) {
                        break 2;
                    }
                }
            }
        }
    }

    protected function scanTextElement()
    {

        if (!$this->match("^([^\r\n]*)"))
            return;

        $this->consumeMatch();
        $text = $this->getMatch(1);

        if (!empty($text)) {

            $token = $this->createToken('text');
            $token->content = trim($text);
            yield $token;
        }
    }


    protected function scanComment()
    {

        if (!$this->match('^\/\/(-)?'))
            return;

        $this->consumeMatch();

        $token = $this->createToken('comment');
        $token->escaped = $this->getMatch(1) ? true : false;

        yield $token;

        foreach ($this->scanTextBlock() as $token)
            yield $token;
    }

    protected function scanFilter()
    {

        if (!$this->match('^:([a-z][a-z0-9\-_]*)?'))
            return;

        $this->consumeMatch();

        $token = $this->createToken('filter');
        $token->name = $this->getMatch(1);

        yield $token;

        foreach ($this->scanTextBlock() as $token)
            yield $token;
    }

    protected function scanImport()
    {

        if (!$this->match('^(?<type>extends|include)(?::(?<filter>[a-z][a-z0-9\-_]*))? (?<path>[a-z0-9\-_\\/\. ]+)', 'i'))
            return;

        $this->consumeMatch();
        $token = $this->createToken($this->getMatch('type'));
        $token->path = $this->getMatch('path');
        $token->filter = $this->getMatch('filter') ? $this->getMatch('filter') : null;

        yield $token;
    }

    protected function scanBlock()
    {

        if (!$this->match('^(?:block|block (append|prepend|replace)|(append|prepend|replace)) ([a-z][a-z0-9\-_]+)?'))
            return;

        $this->consumeMatch();

        $token = $this->createToken('block');
        $token->name = $this->getMatch(3);
        $token->mode = $this->getMatch(1) ? $this->getMatch(1) : (
            $this->getMatch(2) ? $this->getMatch(2) : 'replace'
        );

        yield $token;

        foreach ($this->scanSub() as $token)
            yield $token;
    }

    protected function scanCase()
    {

        if (!$this->match("^case ([^\r\n]+)"))
            return;

        $this->consumeMatch();
        $token = $this->createToken('case');
        $token->subject = $this->getMatch(1);

        yield $token;
    }

    protected function scanWhen()
    {

        if (!$this->match("^when ([^:\r\n]+)"))
            return;

        $this->consumeMatch();
        $token = $this->createToken('when');
        $token->value = $this->getMatch(1);

        yield $token;

        foreach ($this->scanSub() as $token)
            yield $token;
    }

    protected function scanConditional()
    {

        if (!$this->match("^(?:(if|unless|else[ ]?if) ([^\r\n]+)|(else)[^\r\n])"))
            return;

        $this->consumeMatch();
        $token = $this->createToken(
            $this->getMatch(3)
            ? $this->getMatch(3)
            : str_replace(' ', '', $this->getMatch(1))
        );
        $token->subject = $this->getMatch(2) ? $this->getMatch(2) : null;

        yield $token;
    }

    protected function scanEach()
    {

        $nl = "\r\n";
        if (!$this->match('^each\s+\$([a-z][a-z0-9\-_]*)(?:\s*,\s*\$([a-z][a-z0-9\-_]*))\s+in\s+([^'.$nl.']+)', 'i'))
            return;

        $this->consumeMatch();
        $token = $this->createToken('each');
        $token->subject = $this->getMatch(3);
        $token->itemName = $this->getMatch(1);
        $token->keyName = $this->getMatch(2) ? $this->getMatch(2) : null;

        yield $token;
    }

    protected function scanLoop()
    {

        if (!$this->match("^while ([^\r\n]+)"))
            return;

        $this->consumeMatch();
        $token = $this->createToken('while');
        $token->subject = $this->getMatch(1) ? $this->getMatch(1) : null;

        yield $token;
    }

    protected function scanCode()
    {

        if (!$this->match('^(-|[!]?=)'))
            return;

        $this->consumeMatch();
        $token = $this->createToken('code');
        $token->escaped = $this->getMatch(1) === '=';
        yield $token;

        foreach ($this->scanTextBlock() as $token)
            yield $token;
    }

    protected function scanSub()
    {

        if ($this->peek() === ':') {

            $this->consume();
            $token = $this->createToken('sub');
            $this->skip();

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

        if (!$this->match('^(doctype|!!!) ([^\r\n]*)'))
            return;

        $this->consumeMatch();
        $token = $this->createToken('doctype');
        $token->value = $this->getMatch(2);

        yield $token;
    }

    protected function scanTag()
    {

        if (!$this->match('^([a-z][a-z0-9\-_]*)', 'i'))
            return;

        $this->consumeMatch();

        $token = $this->createToken('tag');
        $token->name = $this->getMatch(1);

        yield $token;

        //Make sure classes are scanned on this before we scan the . add-on
        foreach ($this->scanClasses() as $token)
            yield $token;

        foreach($this->scanSub() as $token)
            yield $token;
    }

    protected function scanClasses()
    {

        while($this->match('^\.([a-z][a-z0-9\-_]+)', 'i')) {


            $this->consumeMatch();

            $token = $this->createToken('class');
            $token->name = $this->getMatch(1);
            yield $token;
        }

        foreach($this->scanSub() as $token) {

            yield $token;
        }
    }

    protected function scanId()
    {

        if (!$this->match('^#([a-z][a-z0-9\-_]*)', 'i'))
            return;

        $this->consumeMatch();

        $token = $this->createToken('id');
        $token->name = $this->getMatch(1);
        yield $token;

        //Make sure classes are scanned on this before we scan the . add-on
        foreach ($this->scanClasses() as $token)
            yield $token;

        foreach($this->scanSub() as $token)
            yield $token;
    }

    protected function scanMixin()
    {

        if (!$this->match('^mixin ([a-z][a-z0-9\-_]*)', 'i'))
            return;

        $this->consumeMatch();

        $token = $this->createToken('mixin');
        $token->name = $this->getMatch(1);
        yield $token;

        foreach($this->scanSub() as $token)
            yield $token;
    }

    protected function scanMixinCall()
    {

        if (!$this->match('^\+([a-z][a-z0-9\-_]*)', 'i'))
            return;

        $this->consumeMatch();

        $token = $this->createToken('mixin-call');
        $token->name = $this->getMatch(1);
        yield $token;

        //Make sure classes are scanned on this before we scan the . add-on
        foreach ($this->scanClasses() as $token)
            yield $token;

        foreach($this->scanSub() as $token)
            yield $token;
    }

    protected function scanMerger()
    {

        if (!$this->match('^\&([a-z][a-z0-9\-_]*)', 'i'))
            return;

        $this->consumeMatch();

        $token = $this->createToken('merger');
        $token->name = $this->getMatch(1);
        yield $token;

        foreach($this->scanSub() as $token)
            yield $token;
    }

    protected function scanAttributes()
    {

        if ($this->peek() !== '(')
            return;

        $this->consume();
        $this->skip();

        if ($this->peek() !== ')') {

            $continue = true;
            while(!$this->isAtEnd() && $continue) {

                $token = $this->createToken('attribute');
                $token->name = null;
                $token->value = null;
                $token->escaped = true;

                if ($this->match('^([a-z][a-z0-9\-_]*)', 'i')) {

                    $this->consumeMatch();
                    $token->name = $this->getMatch(1);
                    $this->skip();
                }

                if ($this->peek() === '!') {

                    $token->escaped = false;
                    $this->consume();
                }

                if (!$token->name || $this->peek() === '=') {

                    if ($token->name) {

                        $this->consume();
                        $this->skip();
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

                    $token->value = $value;
                }

                if ($this->peek() === ',') {

                    $this->consume();
                    $this->skip();
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

        //Make sure classes are scanned on this before we scan the . add-on
        foreach ($this->scanClasses() as $token)
            yield $token;

        foreach($this->scanSub() as $token)
            yield $token;
    }

    protected function throwException($message)
    {

        $message = "Failed to parse jade: $message (Line: {$this->_line}, Offset: {$this->_offset})";
        throw new ParseException($message);
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