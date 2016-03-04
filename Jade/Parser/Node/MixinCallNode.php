<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\Node;
use Tale\Jade\Util\AssignmentTrait;
use Tale\Jade\Util\AttributeTrait;
use Tale\Jade\Util\NameTrait;

class MixinCallNode extends Node
{
    use NameTrait;
    use AttributeTrait;
    use AssignmentTrait;
}