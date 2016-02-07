<?php

namespace Tale\Jade\Util;

use Tale\Jade\Parser\Node\AttributeListNode;

trait AttributeTrait
{

    private $_attributes = null;

    public function getAttributes()
    {

        if (!$this->_attributes)
            $this->_attributes = new AttributeListNode;

        return $this->_attributes;
    }
}