<?php

namespace Tale\Jade\Util;

use Tale\Jade\Parser\Node\AttributeListNode;

trait AssignmentTrait
{

    private $assignments = null;

    public function getAssignments()
    {

        if (!$this->assignments)
            $this->assignments = new AttributeListNode;

        return $this->assignments;
    }
}