<?php

namespace Tale\Jade\Parser;

/**
 * Class Node
 * @package Tale\Jade\Parser
 */
class Node
{

    /**
     * @var
     */
    public $type;
    /**
     * @var null
     */
    public $line;
    /**
     * @var null
     */
    public $offset;

    /**
     * @var null
     */
    public $parent;
    /**
     * @var array
     */
    public $children;

    /**
     * @var array
     */
    private $_data;

    /**
     * @param $type
     * @param null $line
     * @param null $offset
     */
    public function __construct($type, $line = null, $offset = null)
    {

        $this->type = $type;
        $this->line = $line;
        $this->offset = $offset;

        $this->parent = null;
        $this->children = [];

        $this->_data = [];
    }

    /**
     * @param Node $node
     * @return mixed
     */
    public function indexOf(Node $node)
    {

        return array_search($node, $this->children, true);
    }

    /**
     * @param Node $node
     * @return $this
     */
    public function append(Node $node)
    {

        $this->children[] = $node;
        $node->parent = $this;

        return $this;
    }

    /**
     * @param Node $node
     * @return $this
     */
    public function prepend(Node $node)
    {

        array_unshift($this->children, $node);
        $node->parent = $this;

        return $this;
    }

    /**
     * @param Node $node
     * @return $this
     */
    public function remove(Node $node)
    {

        $index = $this->indexOf($node);

        if ($index !== false) {

            $this->children[$index]->parent = null;
            array_splice($this->children, $index, 1);
        }

        return $this;
    }

    /**
     * @param Node $node
     * @param Node $newNode
     * @return $this|Node
     */
    public function insertAfter(Node $node, Node $newNode)
    {

        $index = $this->indexOf($node);

        if ($index === false)
            return $this->append($newNode);

        array_splice($this->children, $index + 1, 0, [$newNode]);
        $newNode->parent = $this;

        return $this;
    }

    /**
     * @param Node $node
     * @param Node $newNode
     * @return $this|Node
     */
    public function insertBefore(Node $node, Node $newNode)
    {

        $index = $this->indexOf($node);

        if ($index === false)
            return $this->prepend($newNode);

        array_splice($this->children, $index, 0, [$newNode]);
        $newNode->parent = $this;

        return $this;
    }

    /**
     * @param $type
     * @return \Generator
     */
    public function find($type)
    {

        foreach ($this->children as $node) {

            if ($node->type === $type)
                yield $node;

            foreach ($node->find($type) as $subNode)
                yield $subNode;
        }
    }

    /**
     * @return null
     */
    public function prev()
    {

        $index = $this->parent->indexOf($this) - 1;

        return isset($this->parent->children[$index]) ? $this->parent->children[$index] : null;
    }

    /**
     * @return null
     */
    public function next()
    {

        $index = $this->parent->indexOf($this) + 1;

        return isset($this->parent->children[$index]) ? $this->parent->children[$index] : null;
    }

    /**
     * @param string $indentStyle
     * @param string $newLine
     * @param int $level
     * @return string
     */
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

    /**
     * @param $type
     * @return array
     */
    public function findArray($type)
    {

        return iterator_to_array($this->find($type));
    }

    /**
     * @param int $level
     * @return string
     */
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

    /**
     * @param $key
     * @return bool
     */
    public function __isset($key)
    {

        return isset($this->_data[$key]);
    }

    /**
     * @param $key
     */
    public function __unset($key)
    {

        unset($this->_data[$key]);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function &__get($key)
    {

        return $this->_data[$key];
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {

        $this->_data[$key] = $value;
    }

    /**
     * @return string
     */
    public function __toString()
    {

        return $this->dump();
    }
}