<?php

namespace Tale\Jade\Parser;

interface NodeInterface extends \ArrayAccess, \IteratorAggregate, \Countable
{

    public function getLine();
    public function getOffset();
    public function getOuterNode();
    public function setOuterNode(NodeInterface $node);
    public function getParent();
    public function setParent(NodeInterface $node);
    public function hasChildren();
    public function getChildren();
    public function setChildren(array $children);
    public function getIndexOf(NodeInterface $node);
    public function hasChild(NodeInterface $node);
    public function hasChildAt($index);
    public function getChildAt($index);
    public function setChildAt($index, NodeInterface $node);
    public function getPreviousSibling();
    public function getNextSibling();
    public function appendChild(NodeInterface $node);
    public function prependChild(NodeInterface $node);
    public function removeChild(NodeInterface $node);
    public function removeChildAt($index);
    public function insertBefore(NodeInterface $node, NodeInterface $newNode);
    public function insertAfter(NodeInterface $node, NodeInterface $newNode);
    public function clear();
    public function wrap(NodeInterface $node);
    public function is($className);
    public function find($className);
    public function findArray($className);
    public function dump($level = 0);
    public function __toString();
}