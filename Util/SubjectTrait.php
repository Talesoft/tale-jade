<?php

namespace Tale\Jade\Util;

trait SubjectTrait
{

    private $subject = null;

    public function getSubject()
    {

        return $this->subject;
    }

    public function setSubject($subject)
    {

        $this->subject = $subject;

        return $this;
    }
}