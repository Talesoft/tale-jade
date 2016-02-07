<?php

namespace Tale\Jade\Util;

trait FilterTrait
{

    private $_filter = null;

    /**
     * @return string
     */
    public function getFilter()
    {
        return $this->_filter;
    }

    /**
     * @param string $filter
     *
     * @return $this
     */
    public function setFilter($filter)
    {
        $this->_filter = $filter;

        return $this;
    }
}