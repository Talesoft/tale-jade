<?php

namespace Tale\Jade\Util;

trait NameTrait
{

    private $_name = null;

    /**
     * @return bool
     */
    public function hasName()
    {
        return $this->_name !== null;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->_name = $name;

        return $this;
    }
}