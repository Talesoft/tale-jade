<?php

namespace Tale\Jade\Util;

trait LevelGetTrait
{

    protected $level = 0;

    /**
     * @return int
     */
    public function getLevel()
    {

        return $this->level;
    }
}