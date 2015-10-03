<?php

namespace Tale\Jade;

class Node
{

    public $type;
    public $line;
    public $offset;

    public $parent;
    public $children;

    private $_data;

    public function __construct($type, $line = null, $offset = null)
    {

        $this->type = $type;
        $this->line = $line;
        $this->offset = $offset;

        $this->parent = null;
        $this->children = [];

        $this->_data = [];
    }

    public function indexOf(Node $node)
    {

        return array_search($node, $this->children, true);
    }

    public function append(Node $node)
    {

        $this->children[] = $node;
        $node->parent = $this;

        return $this;
    }

    public function prepend(Node $node)
    {

        array_unshift($this->children, $node);
        $node->parent = $this;

        return $this;
    }

    public function remove(Node $node)
    {

        $index = $this->indexOf($node);

        if ($index !== false) {

            $this->children[$index]->parent = null;
            array_splice($this->children, $index, 1);
        }

        return $this;
    }

    public function insertAfter(Node $node, Node $newNode)
    {

        $index = $this->indexOf($node);

        if ($index === false)
            return $this->append($newNode);

        array_splice($this->children, $index + 1, 0, [$newNode]);

        return $this;
    }

    public function insertBefore(Node $node, Node $newNode)
    {

        $index = $this->indexOf($node);

        if ($index === false)
            return $this->prepend($newNode);

        array_splice($this->children, $index, 0, [$newNode]);

        return $this;
    }

    public function find($type)
    {

        foreach ($this->children as $node) {

            if ($node->type === $type)
                yield $node;

            foreach ($node->find($type) as $subNode)
                yield $subNode;
        }
    }

    public function prev()
    {

        $index = $this->parent->indexOf($this) - 1;

        return isset($this->parent[$index]) ? $this->parent[$index] : null;
    }

    public function next()
    {

        $index = $this->parent->indexOf($this) + 1;

        return isset($this->parent->children[$index]) ? $this->parent->children[$index] : null;
    }

    public function text($indentStyle = '  ', $newLine = "\n", $level = 0)
    {

        $indent = str_repeat($indentStyle, $level);
        $texts = [];
        foreach ($this->children as $child) {

            if ($child->type === 'text') {

                $texts[] = $indent.$child->value;
                $texts[] = $child->text($indentStyle, $newLine, $level + 1);
            }
        }

        return implode($newLine, $texts);
    }

    public function findArray($type)
    {

        return iterator_to_array($this->find($type));
    }

    public function dump($level = 0)
    {

        $export = implode(' ', array_map(function($key, $value) {

            $str = '';
            if (!is_numeric($key))
                $str .= "$key=";

            if ($value)
                $str .= !is_array($value)
                      ? $value
                      : '{'.implode(', ', array_map('trim', array_map('strval', $value))).'}';

            return $str;
        }, array_keys($this->_data), $this->_data));

        $indent = str_repeat('    ', $level);
        $str = $indent.'['.$this->type.(empty($export) ? '' : " $export").']'."\n";
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