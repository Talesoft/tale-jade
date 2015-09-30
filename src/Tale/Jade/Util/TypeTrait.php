<?php

namespace Tale\Jade\Util;

trait TypeTrait
{

    private $_type = null;

    /**
     * @return bool
     */
    public function hasType()
    {
        return $this->_type !== null;
    }

    /**
     * @return string|null
     */
    public function getType()
    {

        return $this->_type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {

        $this->_type = $type;

        return $this;
    }
}