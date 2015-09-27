<?php

namespace Tale\Jade;

use Tale\Jade\Node\BlockNode;
use Tale\Jade\Node\DoctypeNode;
use Tale\Jade\Node\DocumentNode;
use Tale\Jade\Node\ElementNode;
use Tale\Jade\Node\ExpressionNode;
use Tale\Jade\Node\TextNode;

class Parser
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
    private $_textMode;

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

    public function parse($input)
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
        $this->_textMode = false;
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
            $this->scan();
        }

        return $this->_output->getRoot();
    }

    protected function isEol()
    {

        return $this->_offset >= $this->_lineLength;
    }

    protected function peek($length = 1)
    {

        return $this->substr($this->_lineInput, $this->_offset, $length);
    }

    protected function consume($length = 1)
    {

        $this->_lineInput = $this->substr($this->_lineInput, $length);
        return $this;
    }

    protected function peekWhile($callback, $length = 1, $reverse = false)
    {

        if (!is_callable($callback))
            throw new \Exception(
                "Argument 1 passed to peekWhile needs to be callback"
            );

        $token = '';
        while (!$this->isEol()
           && ($reverse
                ? !$callback($string = $this->peek($length))
                : $callback($string = $this->peek($length))
              )
        )
        {

            $this->consume($length);
            $token .= $string;
        }

        return $token;
    }

    protected function skip()
    {

        return $this->peekWhile('ctype_space');
    }

    protected function peekUntil($callback, $length = 1)
    {

        return $this->peekWhile($callback, $length, true);
    }

    protected function match($pattern, $modifiers = '')
    {

        return preg_match(
            "/$pattern/$modifiers",
            $this->_lineInput,
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

    protected function append(NodeBase $node, $enter = true)
    {

        $enteredNode = $this->_output;
        if ($this->_levelDifference > 0 || $this->_output instanceof DocumentNode) {

            //This node is a child-node
            $enteredNode->appendChild($node);
            if ($enter)
                $enteredNode = $node;
        } else if ($this->_levelDifference < 0) {

            //We go the difference of levels up and append it there
            $count = $this->_levelDifference;
            $ref = $enteredNode;
            while($count++ < 1) {

                $ref = $ref->getParent();
            }
            $enteredNode = $ref;
            $ref->appendChild($node);
        } else {

            //We're on the same level, this is a sibling
            $enteredNode = $this->_output->getParent();
            $enteredNode->appendChild($node);
        }

        //Enter this node
        $this->_output = $enteredNode;

        return $this;
    }

    protected function scan()
    {

        $this->scanExtends() or
        $this->scanBlock() or
        $this->scanDoctype() or
        $this->scanTag() or
        $this->scanClasses() or
        $this->scanId() or
        $this->scanAttributes() or
        $this->throwException(
            "Failed to parse line \"{$this->_lineInput}\", no valid Jade syntax"
        );
    }

    protected function scanIndent()
    {

        $indent = $this->skip();

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

    protected function scanExtends()
    {

        if (!$this->match('^extends (.*)$'))
            return false;

        if (!($this->_output instanceof DocumentNode) || $this->_output->hasChildren() || $this->_level !== 0)
            $this->throwException(
                "Extends has to be the very first, unindented instruction you make"
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

        $this->append(new BlockNode($name, $mode));

        return true;
    }

    protected function scanDoctype()
    {

        if (!$this->match('^(doctype|!!!) (.*)$'))
            return false;

        $this->append(new DoctypeNode($this->getMatch(2)));

        return true;
    }

    protected function scanTag($local = false)
    {

        if (!$this->match('^([a-z][a-z0-9\-_]*)', 'i'))
            return false;

        $this->consumeMatch();
        $tag = $this->getMatch(1);

        if (!$local)
            $this->append(new ElementNode());

        if (!$this->_output instanceof ElementNode)
            $this->throwException(
                "Tags can only exist on element nodes"
            );

        $this->_output->setTag($tag);

        $this->scanClasses(true) or
        $this->scanId(true) or
        $this->scanAttributes(true) or
        $this->subScan() or
        $this->scanExpression(true) or
        $this->scanText();

        return true;
    }

    protected function scanClasses($local = false)
    {

        if (!$this->match('^\.([a-z][a-z0-9\-_]*)', 'i'))
            return false;

        $this->consumeMatch();
        $classes = explode('.', $this->getMatch(1));

        if (!$local)
            $this->append(new ElementNode());

        if (!$this->_output instanceof ElementNode)
            $this->throwException(
                "Classes can only exist on element nodes"
            );
        $this->_output->appendClasses($classes);

        $this->scanId(true) or
        $this->scanAttributes(true) or
        $this->subScan() or
        $this->scanExpression(true) or
        $this->scanText();

        return true;
    }

    protected function scanId($local = false)
    {

        if (!$this->match('^#([a-z][a-z0-9\-_]*)', 'i'))
            return false;

        $this->consumeMatch();
        $id = $this->getMatch(1);

        if (!$local)
            $this->append(new ElementNode());

        if (!$this->_output instanceof ElementNode)
            $this->throwException(
                "IDs can only exist on element nodes"
            );
        $this->_output->setAttribute('id', $id);

        $this->scanAttributes(true) or
        $this->subScan() or
        $this->scanExpression(true) or
        $this->scanText();

        return true;
    }


    protected function scanAttributes($local = false)
    {


        if ($this->isEol() || $this->peek() !== '(')
            return false;

        $this->consume();

        if (!$local)
            $this->append(new ElementNode());

        if (!$this->_output instanceof ElementNode)
            $this->throwException(
                "Attributes can only exist on element nodes"
            );

        $this->skip();
        $continue = false;
        do {

            if (!$this->match('^([a-z][a-z0-9\-_]*)'))
                break;

            $name = $this->getMatch(1);

            $this->skip();
            $value = null;
            if (!$this->isEol() && $this->peek() === '=') {

                $this->consume();

                $this->skip();
                $value = null;
                $level = 0;
                do {

                    if ($this->isEol())
                        break;

                    $char = $this->peek();

                    if ($char === '(') {

                        $level++;
                        $this->consume();
                        continue;
                    }

                    if ($char === ')') {

                        if ($level > 0) {

                            $level--;
                            $this->consume();
                            continue;
                        }

                        break;
                    }

                    $value .= $char;
                    $this->consume();
                } while(true);
            }

            $this->_output->setAttribute($name, $value);

            $this->skip();
            if (!$this->isEol() && $this->peek() === ',')
                $continue = true;
            else
                $continue = false;

        } while($continue);

        $this->skip();

        if ($this->isEol() || $this->peek() !== ')')
            $this->throwException(
                "Unclosed attribute block"
            );

        $this->consume();

        $this->scanTag(true) or
        $this->scanClasses(true) or
        $this->scanId(true) or
        $this->scanAttributes(true) or
        $this->subScan() or
        $this->scanExpression(true) or
        $this->scanText();

        return true;
    }

    protected function subScan()
    {

        if ($this->isEol() || $this->peek() !== ':')
            return false;

        $this->consume();

        $this->skip();
        $this->_level++;
        $oldDiff = $this->_levelDifference;
        $this->_levelDifference = 1;

        $this->scanTag(true) or
        $this->scanClasses(true) or
        $this->scanId(true) or
        $this->scanAttributes(true) or
        $this->subScan() or
        $this->scanExpression(true) or
        $this->scanText();

        $this->_level--;
        $this->_levelDifference = $oldDiff;

        return true;
    }

    protected function scanExpression($local = false)
    {

        if (!$this->match('^([!]?=)'))
            return false;

        $this->consumeMatch();
        $text = trim($this->_lineInput);

        $this->append(new ExpressionNode($text, $this->getMatch(1) === '!='), false);

        return true;
    }

    protected function scanText()
    {

        if (!$this->isEol())
            return false;

        $text = trim($this->_lineInput);

        if (empty($text))
            return false;

        $this->apend(new TextNode($text), false);

        return true;
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
}