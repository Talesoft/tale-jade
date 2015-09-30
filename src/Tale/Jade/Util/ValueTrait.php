<?php

namespace Tale\Jade\Util;

trait ValueTrait
{

    private $_value = null;

    /**
     * @return bool
     */
    public function hasValue()
    {
        return $this->_value !== null;
    }

    /**
     * @return string|null
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->_value = $value;

        return $this;
    }
}