<?php

namespace Tale\Jade\Util;

trait VisibleTrait
{

    private $_visible = true;

    public function isVisible()
    {

        return $this->_visible;
    }

    public function setIsVisible($visible)
    {

        $this->_visible = $visible;

        return $this;
    }

    public function show()
    {

        $this->_visible = true;

        return $this;
    }

    public function hide()
    {

        $this->_visible = false;

        return $this;
    }
}