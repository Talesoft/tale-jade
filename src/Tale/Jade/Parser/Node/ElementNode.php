<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\NodeBase;

class ElementNode extends NodeBase
{

    private $_tag;
    private $_attributes;

    public function __construct()
    {

        $this->_tag = null;
        $this->_attributes = [];
    }
}