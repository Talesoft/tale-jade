<?php

namespace Tale\Jade\Util;

trait LevelGetTrait
{

    private $level = 0;

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }
}