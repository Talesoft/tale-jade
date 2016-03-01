<?php
/**
 * The Tale Jade Parser Node Base.
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
 * @copyright  Copyright (c) 2015 Talesoft (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.0
 * @link       http://jade.talesoft.io/docs/files/Parser.Node.html
 * @since      File available since Release 1.0
 */

namespace Tale\Jade\Parser;

use ArrayIterator;
use Tale\Jade\Compiler\Exception;
use Tale\Jade\Util\LevelGetTrait;
use Traversable;

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
 * @copyright  Copyright (c) 2015 Talesoft (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.0
 * @link       http://jade.talesoft.io/docs/classes/Tale.Jade.Parser.Node.html
 * @since      File available since Release 1.0
 */
class Node
{
    use LevelGetTrait;

    /**
     * The line this node was created on.
     *
     * @var int|null
     */
    private $_line;

    /**
     * The offset in a line this node was created on.
     *
     * @var int|null
     */
    private $_offset;

    private $_outerNode;

    /**
     * The parent-node of this node.
     *
     * @var Node|null
     */
    private $_parent;

    /**
     * The children of this node.
     *
     * @var Node[]
     */
    private $_children;

    /**
     * Creates a new, detached node without children or a parent.
     *
     * It can be appended to any node after that
     *
     * The type can be any kind of string
     *
     * @param int|null $line   the line at which we found this node
     * @param int|null $offset the offset in a line we found this node at
     * @param int|null $level the level of indentation this node is at
     */
    public function __construct($line = null, $offset = null, $level = null)
    {

        $this->_line = $line ?: 0;
        $this->_offset = $offset ?: 0;
        $this->_level = $level ?: 0;

        $this->_parent = null;
        $this->_children = [];
    }

    /**
     * @return int|null
     */
    public function getLine()
    {
        return $this->_line;
    }

    /**
     * @param int|null $line
     *
     * @return Node
     */
    public function setLine($line)
    {
        $this->_line = $line;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getOffset()
    {
        return $this->_offset;
    }

    /**
     * @param int|null $offset
     *
     * @return Node
     */
    public function setOffset($offset)
    {
        $this->_offset = $offset;

        return $this;
    }

    /**
     * @return Node
     */
    public function getOuterNode()
    {
        return $this->_outerNode;
    }

    /**
     * @param Node $node
     *
*@return $this
     */
    public function setOuterNode(Node $node)
    {

        $this->_outerNode = $node;

        return $this;
    }

    /**
     * @return Node|null
     */
    public function getParent()
    {
        return $this->_parent;
    }

    /**
     * @param null|Node $parent
     *
     * @return Node
     */
    public function setParent(Node $parent)
    {
        $this->_parent = $parent;

        if ($parent !== null && !$parent->hasChild($this))
            $parent->appendChild($this);

        return $this;
    }

    public function hasChildren()
    {

        return count($this->_children) > 0;
    }

    /**
     * @return Node[]
     */
    public function getChildren()
    {
        return $this->_children;
    }

    /**
     * @param Node[] $children
     *
     * @return Node
     */
    public function setChildren(array $children)
    {

        $this->clear();
        foreach ($children as $child)
            $this->appendChild($child);

        return $this;
    }

    public function getChildAt($index)
    {

        return $this->_children[$index];
    }

    public function setChildAt($index, Node $node)
    {

        if ($index === null)
            return $this->appendChild($node);

        if ($this->hasChildAt($index)) {

            $this->_children[$index]->setParent(null);
        }

        $this->_children[$index] = $node;

        if ($node->getParent() !== $this)
            $node->setParent($this);

        return $this;
    }

    public function hasChildAt($index)
    {

        return isset($this->_children[$index]);
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
    public function getIndexOf(Node $node)
    {

        return array_search($node, $this->_children, true);
    }

    public function hasChild(Node $node)
    {

        return $this->getIndexOf($node) !== false;
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
     * @throws Exception
     */
    public function getPreviousSibling()
    {

        if (!$this->_parent)
            throw new Exception(
                "Failed to get previous sibling of $this.".
                "Node doesn't have a parent."
            );

        $index = $this->_parent->getIndexOf($this) - 1;
        $parentChildren = $this->_parent->getChildren();
        return isset($parentChildren[$index]) ? $parentChildren[$index] : null;
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
     * @throws Exception
     */
    public function getNextSibling()
    {

        if (!$this->_parent)
            throw new Exception(
                "Failed to get next sibling of $this.".
                "Node doesn't have a parent."
            );

        $index = $this->_parent->getIndexOf($this) + 1;
        $parentChildren = $this->_parent->getChildren();
        return isset($parentChildren[$index]) ? $parentChildren[$index] : null;
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
    public function appendChild(Node $node)
    {

        $this->_children[] = $node;

        if ($node->getParent() !== $this)
            $node->setParent($this);

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
    public function prependChild(Node $node)
    {

        array_unshift($this->_children, $node);

        if ($node->getParent() !== $this)
            $node->setParent($this);

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
    public function removeChild(Node $node)
    {

        $index = $this->getIndexOf($node);

        if ($index !== false)
            $this->removeChildAt($index);

        return $this;
    }

    public function removeChildAt($index)
    {

        if (!isset($this->_children[$index]))
            throw new Exception(
                "Failed to remove child: No child at index $index found"
            );

        $this->_children[$index]->setParent(null);
        array_splice($this->_children, $index, 1);

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
*@throws Exception
     * @return $this
     */
    public function insertAfter(Node $node, Node $newNode)
    {

        $index = $this->getIndexOf($node);

        if ($index === false)
            throw new Exception(
                "Failed to insert node after $node: $node is not a sibling ".
                "of $newNode"
            );

        array_splice($this->_children, $index + 1, 0, [$newNode]);

        if ($newNode->getParent() !== $this)
            $newNode->setParent($this);

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
*@throws Exception
     * @return $this
     */
    public function insertBefore(Node $node, Node $newNode)
    {

        $index = $this->getIndexOf($node);

        if ($index === false)
            throw new Exception(
                "Failed to insert node before $node: $node is not a sibling ".
                "of $newNode"
            );

        array_splice($this->_children, $index, 0, [$newNode]);

        if ($newNode->getParent() !== $this)
            $newNode->setParent($this);

        return $this;
    }

    public function clear()
    {

        foreach ($this->_children as $child)
            $child->setParent(null);

        $this->_children = [];

        return $this;
    }

    public function wrap(Node $node)
    {

        $this->_parent->insertBefore($this, $node);
        $this->_parent->removeChild($this);
        $node->appendChild($this);

        return $this;
    }

    public function is($className)
    {

        return is_a($this, $className);
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
     * @param string $className the node type to search for
     *
     * @return \Generator a generator of the found children
     */
    public function find($className)
    {

        foreach ($this->_children as $node) {

            if ($node->is($className))
                yield $node;

            foreach ($node->find($className) as $subNode)
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
     * @param string $className the node type to search for
     *
     * @return array an array containing all found children
     */
    public function findArray($className)
    {

        return iterator_to_array($this->find($className));
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
    public function getText($indentStyle = '  ', $newLine = "\n", $level = 0)
    {

        $indent = str_repeat($indentStyle, $level);
        $texts = [];
        //@TODO: Rework this to use TextToken->getLevel() for indentation
        // (To keep original indentation that was used in jade code)

        foreach ($this->_children as $child) {

            if (is_a($child, TextNode::class)) {

                $texts[] = $indent.$child->getValue();
                $texts[] = $child->getText($indentStyle, $newLine, $level + 1);
            }
        }

        return implode($newLine, $texts);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {

        return new ArrayIterator($this->_children);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {

        return $this->hasChildAt($offset);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {

        return $this->getChildAt($offset);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {

        $this->setChildAt($offset, $value);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     *
     * @return void
     */
    public function offsetUnset($offset)
    {

        $this->removeChildAt($offset);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {

        return count($this->_children);
    }

    /**
     * Dumps the node as a string to ease up debugging.
     *
     * This is also the default-action for __toString on every node
     *
     * @return string the string to debug the node-tree
     */
    protected function dump()
    {

        return '';
    }

    public function getDump($level = 0)
    {

        $indent = str_repeat('    ', $level);
        $str = $indent.'['.basename(get_class($this), 'Node').']'."\n";
        foreach ($this as $child)
            $str .= $child->getDump($level + 1);

        return $str;
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