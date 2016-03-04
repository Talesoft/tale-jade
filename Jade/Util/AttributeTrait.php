<?php

namespace Tale\Jade\Util;

use Tale\Jade\Parser\Node\AttributeListNode;

trait AttributeTrait
{

    private $attributes = null;

    public function getAttributes()
    {

        if (!$this->attributes)
            $this->attributes = new AttributeListNode;

        return $this->attributes;
    }
}