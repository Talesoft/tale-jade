<?php

namespace Tale\Jade;

class NodeBase
{

    private $_parent;
    private $_children;

    public function __construct()
    {

        $this->_parent = null;
        $this->_children = [];
    }

    public function hasParent()
    {

        return $this->_parent !== null;
    }

    /**
     * @return \Tale\Jade\NodeBase|null
     */
    public function getParent()
    {

        return $this->_parent;
    }

    public function setParent(NodeBase $node)
    {

        $this->_parent = $node;

        if (!$node->hasChild($this))
            $node->appendChild($this);

        return $this;
    }

    public function getRoot()
    {

        $current = $this;
        while ($current->hasParent())
            $current = $current->getParent();

        return $current;
    }

    public function hasChildren()
    {

        return count($this->_children) > 0;
    }

    public function getChildren()
    {

        return $this->_children;
    }

    public function appendChildren(array $children)
    {

        foreach ($children as $child)
            $this->appendChild($child);

        return $this;
    }

    public function prependChildren(array $children)
    {

        foreach ($children as $child)
            $this->prependChild($child);

        return $this;
    }

    public function setChildren(array $children)
    {

        $this->removeChildren();
        $this->appendChildren($children);

        return $this;
    }

    public function removeChildren()
    {

        foreach ($this->_children as $child)
            $this->removeChild($child);

        return $this;
    }

    public function hasChild(NodeBase $child)
    {

        return in_array($child, $this->_children, true);
    }

    public function getIndexOf(NodeBase $child)
    {

        return array_search($child, $this->_children, true);
    }

    public function appendChild(NodeBase $child)
    {

        if ($this->hasChild($child))
            $this->removeChild($child);

        $this->_children[] = $child;

        if (!$child->hasParent() || $child->getParent() !== $this)
            $child->setParent($this);

        return $this;
    }

    public function prependChild(NodeBase $child)
    {

        if ($this->hasChild($child))
            $this->removeChild($child);

        array_unshift($this->_children, $child);

        if (!$child->hasParent() || $child->getParent() !== $this)
            $child->setParent($this);

        return $this;
    }

    public function removeChild(NodeBase $child)
    {

        $index = $this->getIndexOf($child);

        if ($index === false)
            return $this;

        array_splice($this->_children, $index, 1);

        return $this;
    }
}