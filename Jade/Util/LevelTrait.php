<?php

namespace Tale\Jade\Util;

trait LevelTrait
{
    use LevelGetTrait;

    /**
     * @param int $level
     *
     * @return $this
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    public function increaseLevel()
    {

        $this->level++;

        return $this;
    }

    public function decreaseLevel()
    {

        $this->level--;

        return $this;
    }
}