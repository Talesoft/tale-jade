<?php

namespace Tale\Jade;

use Tale\Jade\Node\BlockNode;
use Tale\Jade\Node\DoctypeNode;
use Tale\Jade\Node\DocumentNode;
use Tale\Jade\Node\ElementNode;

class Lexer
{

    const INDENT_TAB = "\t";
    const INDENT_SPACE = ' ';

    private $_input;
    private $_length;

    private $_line;
    private $_offset;
    private $_lineInput;
    private $_lineLength;
    private $_level;
    private $_levelDifference;
    private $_indentStyle;
    private $_indentWidth;

    private $_token;
    private $_matches;
    /** @var  \Tale\Jade\NodeBase */
    private $_output;

    public function __construct(array $options = null)
    {

        $this->_options = array_replace([
            'indentStyle' => null,
            'indentWidth' => null,
            'encoding' => 'UTF-8'
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

        $this->_line = 0;
        $this->_offset = 0;
        $this->_lineInput = null;
        $this->_lineLength = 0;
        $this->_level = 0;
        $this->_levelDifference = 0;
        $this->_indentStyle = $this->_options['indentStyle'];
        $this->_indentWidth = $this->_options['indentWidth'];
        $this->_token = '';
        $this->_output = new DocumentNode();


        $lines = array_map('rtrim', explode("\n", $input));


        foreach ($lines as $line) {

            $this->_line++;
            $this->_offset = 0;

            //Ignore empty lines
            $trimmed = trim($line);
            if (empty($trimmed))
                continue;

            $this->_lineInput = $line;
            $this->_lineLength = $this->strlen($this->_lineInput);

            $this->scanIndent();

            $this->scanExtends() or
            $this->scanBlock() or
            $this->scanDoctype() or
            $this->scanElement() or
            $this->throwException(
                "Failed to parse line \"$line\", no valid Jade syntax"
            );
        }

        return $this->_output->getRoot();
    }

    protected function isEol()
    {

        return $this->_offset >= $this->_lineLength;
    }

    protected function peek($length = 1)
    {

        $token = $this->substr($this->_lineInput, $this->_offset, $length);
        return $token;
    }

    protected function consume($token)
    {

        $this->_token .= $token;
        $this->_offset += $this->strlen($token);
        return $this;
    }

    protected function fetch()
    {

        $token = $this->_token;
        $this->_token = '';

        return $token;
    }

    protected function peekWhile($callback, $length = 1, $reverse = false)
    {

        if (!is_callable($callback))
            throw new \Exception(
                "Argument 1 passed to peekWhile needs to be callback"
            );

        while (!$this->isEol()
           && ($reverse
                ? !$callback($char = $this->peek($length))
                : $callback($char = $this->peek($length))
              )
        )
        {

            $this->consume($char);
        }

        return $this->fetch();
    }

    protected function peekUntil($callback, $length = 1)
    {

        return $this->peekWhile($callback, $length, true);
    }

    protected function match($pattern, $modifiers = '')
    {

        return preg_match(
            "/$pattern/$modifiers",
            $this->substr($this->_lineInput, $this->_offset),
            $this->_matches
        );
    }

    protected function consumeMatch()
    {

        return $this->consume($this->_matches[0]);
    }

    protected function getMatch($index)
    {

        return isset($this->_matches[$index]) ? $this->_matches[$index] : null;
    }

    protected function scanIndent()
    {

        //Clear the token on a new line!
        $this->_token = '';
        $indent = $this->peekWhile('ctype_space');

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

            $this->_level = ceil($this->strlen($indent) / $this->_indentWidth);
        } else
            $this->_level = 0;

        //Now we can make out the level of the current indentation
        //If we didnt indent fully, we indent it one level up anyways
        $this->_levelDifference = $this->_level - $oldLevel;
    }

    protected function enter(NodeBase $node)
    {

        if ($this->_levelDifference > 0 || $this->_output instanceof DocumentNode) {

            //This node is a child-node
            $this->_output->appendChild($node);
        } else if ($this->_levelDifference < 0) {

            //We go the difference of levels up and append it there
            $count = $this->_levelDifference;
            $ref = $this->_output;
            while($count++ < 1) {

                $ref = $ref->getParent();
            }
            $ref->appendChild($node);
        } else {

            //We're on the same level, this is a sibling
            $this->_output->getParent()->appendChild($node);
        }

        //Enter this node
        $this->_output = $node;

        return $this;
    }

    protected function scanExtends()
    {

        if (!$this->match('^extends (.*)$'))
            return false;

        if (!($this->_output instanceof DocumentNode) || $this->_output->hasChildren() || $this->_level !== 0)
            $this->throwException(
                "Extends has to be the very first, unindented command you make"
            );

        $this->_output->setExtendPath($this->getMatch(1));

        return true;
    }

    protected function scanBlock()
    {

        if (!$this->match('^block(?: (append|prepend|replace))? (.*)$'))
            return false;

        $name = $this->getMatch(2);
        $mode = $this->getMatch(1);

        if (empty($name))
            $name = null;

        if (empty($mode))
            $mode = null;

        $this->enter(new BlockNode($name, $mode));

        return true;
    }

    protected function scanDoctype()
    {

        if (!$this->match('^(doctype|!!!) (.*)$'))
            return false;

        $this->enter(new DoctypeNode($this->getMatch(2)));

        return true;
    }

    protected function matchElement()
    {

        return $this->match('
            ^
                (?<tag>[a-z][a-z0-9\-_]*)?
                (?<classes>(?:\.[a-z][a-z0-9\-_]*)+)?
                (?<id>(?:\#[a-z][a-z0-9\-_]*))?
        ', 'ix');
    }

    protected function scanElement()
    {

        if (!$this->matchElement())
            return false;

        $tag = $this->getMatch('tag');
        $classes = $this->getMatch('classes');
        $id = $this->getMatch('id');

        var_dump("EL: $tag -> $classes -> $id");
        $this->enter(new ElementNode($this->getMatch(1)));

        $this->consumeMatch();


        $this->scanAttributes();
        $this->scanSubElement();




        return true;
    }

    protected function scanAttributes()
    {

        if ($this->peek('(')) {

            $this->consume('(');

        }
    }

    protected function scanSubElement()
    {

        //We can add insta-child-elements with :
        if ($this->peek() === ':') {

            $this->consume(':');
            $this->peekWhile('ctype_space');
            $this->_level++;
            $oldDiff = $this->_levelDifference;
            $this->_levelDifference = 1;
            $this->scanElement();
            $this->_level--;
            $this->_levelDifference = $oldDiff;

            return true;
        }

        return false;
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
}