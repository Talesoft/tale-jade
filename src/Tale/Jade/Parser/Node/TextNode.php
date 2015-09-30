<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\NodeBase;
use Tale\Jade\Util\ValueTrait;

class TextNode extends NodeBase
{

    use ValueTrait;

    public function append($text)
    {

        $this->setValue($this->getValue().$text);

        return $this;
    }

}