<?php

namespace Tale\Jade\Node;

use Tale\Jade\NodeBase;

class BlockNode extends NodeBase
{

    private $_name;
    private $_mode;

    public function __construct($name = null, $mode = null)
    {
        parent::__construct();

        $this->_name = $name;
        $this->_mode = $mode ? $mode : 'replace';
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getMode()
    {
        return $this->_mode;
    }
}