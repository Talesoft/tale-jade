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
        $this->_level = $level;

        return $this;
    }

    public function increaseLevel()
    {

        $this->_level++;

        return $this;
    }

    public function decreaseLevel()
    {

        $this->_level--;

        return $this;
    }
}