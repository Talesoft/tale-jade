<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer;

class EachToken extends Lexer\TokenBase
{
    private $_itemName;
    private $_keyName;

    public function __construct(Lexer $lexer)
    {

        parent::__construct($lexer);

        $this->_itemName = null;
        $this->_keyName = null;
    }

    /**
     * @return null
     */
    public function getItemName()
    {

        return $this->_itemName;
    }

    /**
     * @param null $itemName
     *
     * @return EachToken
     */
    public function setItemName($itemName)
    {

        $this->_itemName = $itemName;

        return $this;
    }

    /**
     * @return null
     */
    public function getKeyName()
    {

        return $this->_keyName;
    }

    /**
     * @param null $keyName
     *
     * @return EachToken
     */
    public function setKeyName($keyName)
    {

        $this->_keyName = $keyName;

        return $this;
    }
}