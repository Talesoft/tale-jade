<?php

namespace Tale\Jade\Node;


class ExpressionNode extends TextNode
{

    private $_escaped;

    public function __construct($text = null, $escaped = false)
    {
        parent::__construct($text);

        $this->_escaped = $escaped;
    }

    public function isEscaped()
    {

        return $this->_escaped;
    }

    public function escape()
    {

        $this->_escaped = true;

        return $this;
    }
}