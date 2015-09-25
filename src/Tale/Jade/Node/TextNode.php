<?php

namespace Tale\Jade\Node;

use Tale\Jade\NodeBase;

class TextNode extends NodeBase
{

    private $_text;

    public function __construct($text = null)
    {
        parent::__construct();

        $this->_text = $text;
    }

    public function hasText()
    {
        return $this->_text !== null;
    }

    public function getText()
    {
        return $this->_text;
    }

    public function setText($text)
    {
        $this->_text = $text;

        return $this;
    }
}