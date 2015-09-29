<?php

namespace Tale\Jade\Lexer;

use Tale\Jade\Lexer;

abstract class TokenBase
{

    private $_lexer;
    private $_line;
    private $_offset;

    public function __construct(Lexer $lexer)
    {
        $this->_lexer = $lexer;
        $this->_line = $lexer->getLine();
        $this->_offset = $lexer->getOffset();
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

    protected function export() {

        return [];
    }

    public function __toString()
    {

        $exports = $this->export();
        $export = implode(' ', array_map(function($key, $value) {

            $str = '';
            if (!is_numeric($key))
                $str .= "$key=";

            if ($value)
                $str .= $value;

            return $str;
        }, array_keys($exports), $exports));

        return '['.substr(basename(get_class($this)), 0, -5).(empty($export) ? '' : " $export").']';
    }
}