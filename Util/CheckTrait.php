<?php

namespace Tale\Jade\Util;

trait CheckTrait
{

    private $checked = true;

    public function isChecked()
    {

        return $this->checked;
    }

    public function setIsChecked($checked)
    {

        $this->checked = $checked;

        return $this;
    }

    public function check()
    {

        $this->checked = true;

        return $this;
    }

    public function uncheck()
    {

        $this->checked = false;

        return $this;
    }
}