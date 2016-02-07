<?php

namespace Tale\Jade\Util;

trait CheckTrait
{

    private $_checked = true;

    public function isChecked()
    {

        return $this->_checked;
    }

    public function setIsChecked($checked)
    {

        $this->_checked = $checked;

        return $this;
    }

    public function check()
    {

        $this->_checked = true;

        return $this;
    }

    public function uncheck()
    {

        $this->_checked = false;

        return $this;
    }
}