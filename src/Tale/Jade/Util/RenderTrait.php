<?php

namespace Tale\Jade\Util;

trait RenderTrait
{

    private $_render = false;

    public function isRendered()
    {

        return $this->_render;
    }

    public function render()
    {

        $this->_render = true;

        return $this;
    }

    public function unrender()
    {

        $this->_render = false;

        return $this;
    }
}