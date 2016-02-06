<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class ImportToken extends TokenBase
{
    use TypeTrait;

    private $_filter = null;
    private $_path = null;

    /**
     * @return string
     */
    public function getFilter()
    {
        return $this->_filter;
    }

    /**
     * @param string $filter
     *
     * @return ImportToken
     */
    public function setFilter($filter)
    {
        $this->_filter = $filter;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * @param string $path
     *
     * @return ImportToken
     */
    public function setPath($path)
    {
        $this->_path = $path;

        return $this;
    }

    protected function dump()
    {
        return [
            'type' => $this->getType(),
            'filter' => $this->getFilter(),
            'path' => $this->getPath()
        ];
    }
}