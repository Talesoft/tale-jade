<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\NodeBase;
use Tale\Jade\Util\AssignmentTrait;
use Tale\Jade\Util\AttributeTrait;
use Tale\Jade\Util\NameTrait;

class MixinNode extends NodeBase
{
    use NameTrait;
    use AttributeTrait;
    use AssignmentTrait;
}