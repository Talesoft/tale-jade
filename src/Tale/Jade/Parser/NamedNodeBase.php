<?php

namespace Tale\Jade\Parser;


abstract class NamedNodeBase extends NodeBase
{

    private $_name;

    public function __construct()
    {

        parent::__construct();

        $this->_name = null;
    }

    /**
     * @return string
     */
    public function getName()
    {

        return $this->_name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {

        $this->_name = $name;

        return $this;
    }
}