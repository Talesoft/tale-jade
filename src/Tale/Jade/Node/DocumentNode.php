<?php

namespace Tale\Jade\Node;

use Tale\Jade\NodeBase;

class DocumentNode extends NodeBase
{

    private $_extendPath;

    public function __construct()
    {
        parent::__construct();

        $this->_extendPath = null;
    }

    public function hasExtendPath()
    {

        return $this->_extendPath !== null;
    }

    public function getExtendPath()
    {

        return $this->_extendPath;
    }

    public function setExtendPath($path)
    {

        $this->_extendPath = $path;

        return $this;
    }
}