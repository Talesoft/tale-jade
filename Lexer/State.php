<?php

namespace Tale\Jade\Lexer;

use Tale\ConfigurableTrait;
use Tale\Jade\Lexer;
use Tale\Jade\LexerException;
use Tale\Jade\Util\LevelTrait;

class State
{
    use ConfigurableTrait;
    use LevelTrait;

    /** @var Reader $reader */
    private $reader;
    private $indentStyle;
    private $indentWidth;

    public function __construct(array $options)
    {

        $this->defineOptions([
            'input' => '',
            'encoding' => 'UTF-8',
            'level' => 0,
            'indentWidth' => null,
            'indentStyle' => null
        ], $options);

        $this->reader = new Reader(
            $this->options['input'],
            $this->options['encoding']
        );
        $this->indentStyle = $this->options['indentWidth'];
        $this->indentWidth = $this->options['indentStyle'];
        $this->setLevel($this->options['level']);

        $this->reader->normalize();
    }

    /**
     * @return Reader
     */
    public function getReader()
    {

        return $this->reader;
    }

    /**
     * @return string
     */
    public function getIndentStyle()
    {

        return $this->indentStyle;
    }

    public function setIndentStyle($indentStyle)
    {

        if (!in_array($indentStyle, [null, Lexer::INDENT_TAB, Lexer::INDENT_SPACE]))
            throw new \InvalidArgumentException(
                "indentStyle needs to be null or one of the INDENT_* constants of the lexer"
            );

        $this->indentStyle = $indentStyle;

        return $this;
    }

    /**
     * @return int
     */
    public function getIndentWidth()
    {
        return $this->indentWidth;
    }

    public function setIndentWidth($indentWidth)
    {

        if (!is_null($indentWidth) &&
            (!is_int($indentWidth) || $indentWidth < 1)
        )
            throw new \InvalidArgumentException(
                "indentWidth needs to be null or an integer above 0"
            );

        $this->indentWidth = $indentWidth;

        return $this;
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
     * @throws LexerException
     */
    public function scan($scanners)
    {

        $scanners = is_array($scanners) ? $scanners : [$scanners];
        foreach ($scanners as $name => $scanner) {

            if (!is_subclass_of($scanner, ScannerInterface::class))
                throw new \InvalidArgumentException(
                    "Passed scanner is not a valid ".ScannerInterface::class
                );

            /** @var ScannerInterface $scanner */
            $scanner = $scanner instanceof ScannerInterface ? $scanner : new $scanner();
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

        while ($this->reader->hasLength()) {

            $success = false;
            foreach ($this->scan($scanners) as $token) {

                $success = true;
                yield $token;
            }

            if (!$success)
                break;
        }

        if ($this->reader->hasLength() && $required)
            $this->throwException(
                "Unexpected ".$this->reader->peek(20)
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

        if (!is_subclass_of($className, TokenInterface::class))
            $this->throwException(
                "$className is not a valid token class"
            );

        return new $className(
            $this->getReader()->getLine(),
            $this->getReader()->getOffset(),
            $this->getLevel()
        );
    }

    public function scanToken($className, $pattern, $modifiers = null)
    {

        if (!$this->reader->match($pattern, $modifiers))
            return;

        $data = $this->reader->getMatchData();

        $token = $this->createToken($className);
        $this->reader->consume();
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
     * @throws LexerException
     */
    public function throwException($message)
    {

        $pattern = "Failed to lex: %s \nNear: %s \nLine: %s \nOffset: %s \nPosition: %s";

        throw new LexerException(vsprintf($pattern, [
            $message,
            $this->reader->peek(20),
            $this->reader->getLine(),
            $this->reader->getOffset(),
            $this->reader->getPosition()
        ]));
    }
}