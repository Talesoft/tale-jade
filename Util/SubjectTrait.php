<?php

namespace Tale\Jade\Util;

trait SubjectTrait
{

    private $_subject = null;

    public function getSubject()
    {

        return $this->_subject;
    }

    public function setSubject($subject)
    {

        $this->_subject = $subject;

        return $this;
    }
}