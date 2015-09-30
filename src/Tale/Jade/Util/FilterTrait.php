<?php

namespace Tale\Jade\Util;

trait FilterTrait
{

    private $_filter = null;

    public function hasFilter()
    {

        return $this->_filter !== null;
    }

    /**
     * @return string|null
     */
    public function getFilter()
    {

        return $this->_filter;
    }

    /**
     * @param string $filter
     * @return $this
     */
    public function setFilter($filter)
    {

        $this->_filter = $filter;

        return $this;
    }

}