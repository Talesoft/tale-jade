<?php

namespace Tale\Jade\Util;

use Tale\Jade\Parser\Node\AttributeNode;
use Tale\Jade\Parser\NodeBase;

trait AttributesTrait
{

    private $_attributes = [];

    public function getAttributes()
    {
        return $this->_attributes;
    }

    public function appendAttribute(AttributeNode $attribute)
    {

        if ($this instanceof NodeBase)
            $this->appendChild($attribute);

        $this->_attributes[] = $attribute;

        return $this;
    }
}