<?php

namespace Tale\Jade;

class Node
{

    public $type;
    public $children;

    private $_data;

    public function __construct($type)
    {

        $this->type = $type;
        $this->children = [];
        $this->_data = [];
    }

    public function dump($level = 0)
    {

        $export = implode(' ', array_map(function($key, $value) {

            $str = '';
            if (!is_numeric($key))
                $str .= "$key=";

            if ($value)
                $str .= $value;

            return $str;
        }, array_keys($this->_data), $this->_data));

        $indent = str_repeat('    ', $level);
        $str = $indent.'['.basename(get_class($this), 'Node').(empty($export) ? '' : " $export").']'."\n";
        foreach ($this->children as $child)
            $str .= $child->dump($level + 1);

        return $str;
    }

    public function &__get($key)
    {

        return $this->_data[$key];
    }

    public function __set($key, $value)
    {

        $this->_data[$key] = $value;
    }

    public function __toString()
    {

        return $this->dump();
    }
}