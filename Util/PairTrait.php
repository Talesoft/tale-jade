<?php

namespace Tale\Jade\Util;

trait PairTrait
{

    private $_item = null;
    private $_key = null;

    /**
     * @return string
     */
    public function getItem()
    {
        return $this->_item;
    }

    /**
     * @param string $itemName
     *
     * @return $this
     */
    public function setItem($itemName)
    {
        $this->_item = $itemName;

        return $this;
    }

    /**
     * @return null
     */
    public function getKey()
    {
        return $this->_key;
    }

    /**
     * @param string $keyName
     *
     * @return $this
     */
    public function setKey($keyName)
    {
        $this->_key = $keyName;

        return $this;
    }
}