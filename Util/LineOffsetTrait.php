<?php

namespace Tale\Jade\Util;

trait LineOffsetTrait
{

    private $line = 0;
    private $offset = 0;

    /**
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }
}