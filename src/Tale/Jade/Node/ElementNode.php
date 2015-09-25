<?php

namespace Tale\Jade\Node;

class ElementNode extends TextNode
{

    private $_tag;
    private $_attributes;

    public function __construct($tag, array $attributes = null, $text = null)
    {
        parent::__construct($text);

        $this->_tag = $tag;
        $this->_attributes = $attributes ? $attributes : [];
    }
}