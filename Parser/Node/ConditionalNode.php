<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\NodeBase;
use Tale\Jade\Util\NameTrait;
use Tale\Jade\Util\SubjectTrait;

class ConditionalNode extends NodeBase
{
    use NameTrait;
    use SubjectTrait;
}