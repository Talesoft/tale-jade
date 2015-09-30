<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\NodeBase;
use Tale\Jade\Util\AttributesTrait;

class ElementNode extends NodeBase
{

    use AttributesTrait;

    private $_tag;
    private $_assignments;

    public function __construct()
    {
        parent::__construct();

        $this->_tag = null;
        $this->_attributes = [];
    }

    /**
     * @return null
     */
    public function getTag()
    {
        return $this->_tag;
    }

    /**
     * @param null $tag
     * @return $this
     */
    public function setTag($tag)
    {
        $this->_tag = $tag;

        return $this;
    }



    public function getAssignments()
    {

        return $this->_assignments;
    }

    public function appendAssignment(AssignmentNode $assignment)
    {

        $this->appendChild($assignment);
        $this->_assignments[] = $assignment;

        return $this;
    }
}