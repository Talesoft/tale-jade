<?php

namespace Tale\Jade\Util;

trait VisibleTrait
{

    private $visible = true;

    public function isVisible()
    {

        return $this->visible;
    }

    public function setIsVisible($visible)
    {

        $this->visible = $visible;

        return $this;
    }

    public function show()
    {

        $this->visible = true;

        return $this;
    }

    public function hide()
    {

        $this->visible = false;

        return $this;
    }
}