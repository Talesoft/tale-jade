<?php

namespace Tale\Jade\Lexer;

abstract class TokenBase implements TokenInterface
{

    private $_line;
    private $_offset;
    private $_level;

    public function __construct($line, $offset, $level)
    {

        $this->_line = $line;
        $this->_offset = $offset;
        $this->_level = $level;
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

    protected function dump()
    {

        return [];
    }

    public function __toString()
    {

        $name = basename(get_called_class(), 'Token');
        $line = $this->getLine();
        $offset = $this->getOffset();
        $data = $this->dump();
        $str = "[$name($line:$offset)";

        if (count($data)) {

            $str .= ' '.implode(', ', array_map(function ($key, $value) {

                return "$key=$value";
            }, array_keys($data), $data));
        }

        $str .= ']';

        return $str;
    }
}