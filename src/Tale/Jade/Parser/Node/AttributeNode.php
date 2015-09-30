<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\NodeBase;
use Tale\Jade\Util\EscapeTrait;
use Tale\Jade\Util\NameTrait;
use Tale\Jade\Util\ValueTrait;

class AttributeNode extends NodeBase
{

    use NameTrait;
    use ValueTrait;
    use EscapeTrait;

    public function export()
    {

        return [$this->getName() => $this->getValue()];
    }
}