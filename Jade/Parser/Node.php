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

use Tale\Jade\Parser\Node\TextNode;
use Tale\Jade\Util\LevelGetTrait;
use Tale\Reader\LineOffsetTrait;
use Tale\Tree\Node as TreeNode;

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
class Node extends TreeNode implements NodeInterface
{
    use LineOffsetTrait;
    use LevelGetTrait;

    private $outerNode;

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
     * @param NodeInterface $parent the parent of this node
     * @param NodeInterface[] $children the children of this node
     */
    public function __construct(
        $line = null,
        $offset = null,
        $level = null,
        NodeInterface $parent = null,
        array $children = null
    )
    {
        parent::__construct($parent, $children);

        $this->line = $line ?: 0;
        $this->offset = $offset ?: 0;
        $this->level = $level ?: 0;
        $this->outerNode = null;
    }

    /**
     * @return NodeInterface
     */
    public function getOuterNode()
    {
        return $this->outerNode;
    }

    /**
     * @param NodeInterface $node
     *
     * @return $this
     */
    public function setOuterNode(NodeInterface $node = null)
    {

        $this->outerNode = $node;

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
     * @param string $className the node type to search for
     *
     * @return \Generator a generator of the found children
     */
    public function findByClassName($className)
    {

        return $this->find(function (NodeInterface $node) use ($className) {

            return is_a($node, $className);
        });
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
    public function findArrayByClassName($className)
    {
        return iterator_to_array($this->findByClassName($className));
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
        foreach ($this->getChildren() as $child) {

            if ($child instanceof TextNode) {

                $texts[] = $indent.$child->getValue();
                $texts[] = $child->getText($indentStyle, $newLine, $level + 1);
            }
        }
        return implode($newLine, $texts);
    }
}