<?php

namespace Tale\Jade\Lexer\Token;

use Tale\Jade\Lexer\TokenBase;

class EachToken extends TokenBase
{
    use SubjectTrait;

    private $_itemName = null;
    private $_keyName = null;

    /**
     * @return string
     */
    public function getItemName()
    {
        return $this->_itemName;
    }

    /**
     * @param string $itemName
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
     * @param string $keyName
     *
     * @return EachToken
     */
    public function setKeyName($keyName)
    {
        $this->_keyName = $keyName;

        return $this;
    }

    protected function dump()
    {
        return [
            'itemName' => $this->getItemName(),
            'keyName' => $this->getKeyName(),
            'subject' => $this->getSubject()
        ];
    }
}