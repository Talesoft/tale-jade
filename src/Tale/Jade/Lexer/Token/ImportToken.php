<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\TokenBase;

class ImportToken extends TokenBase
{

    private $_type;
    private $_path;
    private $_filter;

    public function __construct(Lexer $lexer)
    {

        parent::__construct($lexer);

        $this->_type = null;
        $this->_path = null;
        $this->_filter = null;
    }

    /**
     * @return string|null
     */
    public function getType()
    {

        return $this->_type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {

        $this->_type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPath()
    {

        return $this->_path;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {

        $this->_path = $path;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFilter()
    {

        return $this->_filter;
    }

    /**
     * @param string $filter
     * @return $this
     */
    public function setFilter($filter)
    {

        $this->_filter = $filter;

        return $this;
    }


}