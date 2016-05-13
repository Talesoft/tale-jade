<?php
/**
 * The Tale Jade Parser Node.
 *
 * Contains a Node-class that keeps parent-child relations and builds
 * a tree this way.
 *
 * It's used to build the Abstract Syntax Tree (AST) in the Parser
 *
 * This file is part of the Tale Jade Template Engine for PHP
 *
 * LICENSE:
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * @category   Presentation
 * @package    Tale\Jade\Parser
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/files/Parser.Node.html
 * @since      File available since Release 1.0
 */

namespace Tale\Jade\Parser;

/**
 * Represents a node in the AST the parser generates.
 *
 * A node has children and always tries to reference its parents
 *
 * It also has some utility methods to work with those nodes
 *
 * @category   Presentation
 * @package    Tale\Jade\Parser
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/classes/Tale.Jade.Parser.Node.html
 * @since      File available since Release 1.0
 */
class Node
{

    /**
     * The type of the node, can be a string of any kind.
     *
     * @var string
     */
    public $type;

    /**
     * The line this node was created on.
     *
     * @var int|null
     */
    public $line;

    /**
     * The offset in a line this node was created on.
     *
     * @var int|null
     */
    public $offset;

    /**
     * The parent-node of this node.
     *
     * @var Node|null
     */
    public $parent;

    /**
     * The children of this node.
     *
     * @var Node[]
     */
    public $children;

    /**
     * The data associated with this node.
     *
     * These get set via __get and __set
     *
     * @var array
     */
    private $data;

    /**
     * Creates a new, detached node without children or a parent.
     *
     * It can be appended to any node after that
     *
     * The type can be any kind of string
     * 
     * @param string   $type   the type of this node
     * @param int|null $line   the line at which we found this node
     * @param int|null $offset the offset in a line we found this node at
     */
    public function __construct($type, $line = null, $offset = null)
    {

        $this->type = $type;
        $this->line = $line;
        $this->offset = $offset;

        $this->parent = null;
        $this->children = [];

        $this->data = [];
    }

    /**
     * Returns the position of the given node inside this node.
     *
     * [element:a]
     *    (0)[element:b]
     *    (1)[element:c]
     *    (2)[element:d]
     *
     * [element:a]->indexOf([element:d]) === 2
     *
     * @see array_search
     *
     * @param Node $node the child-node to get the index of
     *
     * @return int|false
     */
    public function indexOf(Node $node)
    {

        return array_search($node, $this->children, true);
    }

    /**
     * Returns the previous sibling of this element or null, if if there isn't any.
     *
     * [element:a]
     *    (0)[element:b]
     *    (1)[element:c]
     *
     * [element:c]->prev() === [element:b]
     *
     * @return Node|null
     */
    public function prev()
    {

        $index = $this->parent->indexOf($this) - 1;

        return isset($this->parent->children[$index]) ? $this->parent->children[$index] : null;
    }

    /**
     * Returns the next sibling of this element or null, if if there isn't any.
     *
     * [element:a]
     *    (0)[element:b]
     *    (1)[element:c]
     *
     * [element:b]->next() === [element:c]
     *
     * @return Node|null
     */
    public function next()
    {

        $index = $this->parent->indexOf($this) + 1;

        return isset($this->parent->children[$index]) ? $this->parent->children[$index] : null;
    }

    /**
     * Appends the given node to this node's children.
     *
     * This also sets the parent of the given child to this node
     *
     * [element:a]
     *    (0)[element:b]
     *    (1)[element:c]
     *
     * [element:a]->append([element:d])
     *
     * [element:a]
     *    (0)[element:b]
     *    (1)[element:c]
     *    (2)[element:d]
     *
     * @param Node $node the new child node to be appended
     *
     * @return $this
     */
    public function append(Node $node)
    {

        $this->children[] = $node;
        $node->parent = $this;

        return $this;
    }

    /**
     * Prepends the given node to this node's children.
     *
     * This also sets the parent of the given child to this node
     *
     * [element:a]
     *    (0)[element:b]
     *    (1)[element:c]
     *
     * [element:a]->prepend([element:d])
     *
     * [element:a]
     *    (0)[element:d]
     *    (1)[element:b]
     *    (2)[element:c]
     *
     * @param Node $node the new child node to be prepended
     *
     * @return $this
     */
    public function prepend(Node $node)
    {

        array_unshift($this->children, $node);
        $node->parent = $this;

        return $this;
    }

    /**
     * Removes the given child node from this node's children.
     *
     * The parent of the given child node will be set to null
     *
     * [element:a]
     *    (0)[element:b]
     *    (1)[element:c]
     *    (2)[element:d]
     *
     * [element:a]->remove([element:c])
     *
     * [element:a]
     *    (0)[element:b]
     *    (1)[element:d]
     *
     * @param Node $node the node to remove from this node's children
     *
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
     * Inserts the second given node after the first given node
     * inside this node's children.
     *
     * This allows fine control over the node's children
     *
     * The new nodes parent will be set to this node
     *
     * [element:a]
     *    (0)[element:b]
     *    (1)[element:c]
     *
     * [element:a]->insertAfter([element:b], [element:d])
     *
     * [element:a]
     *    (0)[element:b]
     *    (1)[element:d]
     *    (2)[element:c]
     *
     * @param Node $node    the child node of this node's children the new
     *                      node will be inserted after
     * @param Node $newNode the new node that will be inserted after the
     *                      first node
     *
     * @return $this
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
     * Inserts the second given node before the first given node
     * inside this node's children.
     *
     * This allows fine control over the node's children
     *
     * The new nodes parent will be set to this node
     *
     * [element:a]
     *    (0)[element:b]
     *    (1)[element:c]
     *
     * [element:a]->insertBefore([element:c], [element:d])
     *
     * [element:a]
     *    (0)[element:b]
     *    (1)[element:d]
     *    (2)[element:c]
     *
     * @param Node $node    the child node of this node's children the new
     *                      node will be inserted before
     * @param Node $newNode the new node that will be inserted before
     *                      the first node
     *
     * @return $this
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
     * Finds nodes with the given type inside this nodes children.
     *
     * Plus all it's children-children recursively and returns a generator
     * providing them
     *
     * This is used to collect all blocks, imports and mixins and handle them
     * in a special way
     *
     * If you need a normal array, use ->findArray() instead instead
     *
     * @param string $type the node type to search for
     *
     * @return \Generator a generator of the found children
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
     * Finds nodes with the given type inside this nodes children.
     *
     * Plus all it's children-children recursively and returns an array
     * with all of them
     *
     * I you want to do further searching on it, you should
     * rather use the Generator-version ->find() to improve memory-usage
     *
     * @param string $type the node type to search for
     *
     * @return array an array containing all found children
     */
    public function findArray($type)
    {

        return iterator_to_array($this->find($type));
    }


    /**
     * Returns all text and child-texts in a single text.
     *
     * You can control the text-style with the arguments
     *
     * @param string $indentStyle the indentation to use (multiplies with level)
     * @param string $newLine     the new-line style to use
     * @param int    $level       the initial indentation level
     *
     * @return string the compiled text-block
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
     * Dumps the node as a string to ease up debugging.
     *
     * This is also the default-action for __toString on every node
     *
     * The result will look like this:
     *
     * [element tag=a expands=[element tag=b]]
     *    [element tag=c attributes={[attribute name=d name=e]}]
     *
     * @param int $level the initial indentation level
     *
     * @return string the string to debug the node-tree
     */
    public function dump($level = 0)
    {

        $export = implode(' ', array_map(function ($key, $value) {

            $str = '';
            if (!is_numeric($key))
                $str .= "$key=";

            if ($value)
                $str .= !is_array($value)
                    ? $value
                    : '{'.implode(', ', array_map('trim', array_map('strval', $value))).'}';

            return $str;
        }, array_keys($this->data), $this->data));

        $indent = str_repeat('    ', $level);
        $str = $indent.'['.$this->type.(empty($export) ? '' : " $export").']'."\n";
        foreach ($this->children as $child)
            $str .= $child->dump($level + 1);

        return trim($str);
    }

    /**
     * Gets called when isset() is called on this node's properties.
     *
     * Redirects the call to the $_data storage
     *
     * @param string $key the name of the property to check
     *
     * @return bool
     */
    public function __isset($key)
    {

        return isset($this->data[$key]);
    }

    /**
     * Gets called when unset() is called on this node's properties.
     *
     * Redirects the call to $_data storage
     *
     * @param string $key the name of the property to unset
     */
    public function __unset($key)
    {

        unset($this->data[$key]);
    }

    /**
     * Gets called when a property is read from this node.
     *
     * Redirects to the $_data storage and returns a reference to that value
     *
     * @param string $key the name of the property that is read
     *
     * @return mixed a reference to the value of the property
     */
    public function &__get($key)
    {

        return $this->data[$key];
    }

    /**
     * Gets called when a property is written on this node instance.
     *
     * Redirects to the $_data storage and sets a key with that value there
     *
     * @param string $key   the name of the property to be written
     * @param mixed  $value the value of that property
     */
    public function __set($key, $value)
    {

        $this->data[$key] = $value;
    }

    /**
     * Gets called when this node instance is casted to a string in any way
     *
     * (echo, (string), strval, string operations, ., etc.)
     *
     * Calls ->dump() and dumps a debuggable text-representation of this node
     * and all of its child-nodes
     *
     * @return string a debuggable text-representation of this node tree
     */
    public function __toString()
    {

        return $this->dump();
    }
}