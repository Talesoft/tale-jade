<?php

namespace Tale\Jade\Util;

use Tale\Jade\Parser\Node\AttributeListNode;

trait AssignmentTrait
{

    private $_assignments = null;

    public function getAssignments()
    {

        if (!$this->_assignments)
            $this->_assignments = new AttributeListNode;

        return $this->_assignments;
    }
}