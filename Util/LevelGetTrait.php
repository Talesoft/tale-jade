<?php

namespace Tale\Jade\Util;

trait LevelGetTrait
{

    private $_level = 0;

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->_level;
    }
}