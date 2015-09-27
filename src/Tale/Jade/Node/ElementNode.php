<?php

namespace Tale\Jade\Node;

use Tale\Jade\NodeBase;

class ElementNode extends NodeBase
{

    private $_tag;
    private $_attributes;

    public function __construct($tag = null , array $attributes = null)
    {
        parent::__construct();

        $this->_tag = $tag ? $tag : 'div';
        $this->_attributes = $attributes ? $attributes : [];
    }

    public function getTag()
    {
        return $this->_tag;
    }

    public function setTag($tag)
    {

        $this->_tag = $tag;

        return $this;
    }

    public function hasAttributes()
    {

        return count($this->_attributes) > 0;
    }

    public function getAttributes()
    {

        return $this->_attributes;
    }

    public function setAttributes(array $attributes)
    {

        $this->_attributes = $attributes;

        return $this;
    }

    public function hasAttribute($name)
    {

        return isset($this->_attributes[$name]);
    }

    public function getAttribute($name)
    {

        return $this->_attributes[$name];
    }

    public function setAttribute($name, $value)
    {

        $this->_attributes[$name] = $value;

        return $this;
    }

    public function appendClasses(array $classes)
    {

        $currentClasses = $this->hasAttribute('class') ? explode(' ', $this->getAttribute('class')) : [];
        return $this->setAttribute('class', implode(' ', array_merge($currentClasses, $classes)));
    }
}