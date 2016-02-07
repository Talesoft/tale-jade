<?php

namespace Tale\Jade\Util;

trait BlockTrait
{

    private $_block = false;

    public function isBlock()
    {

        return $this->_block;
    }

    public function setIsBlock($block)
    {

        $this->_block = $block;

        return $this;
    }
}