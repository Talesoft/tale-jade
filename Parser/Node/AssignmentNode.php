<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\Node;
use Tale\Jade\Util\AttributeTrait;
use Tale\Jade\Util\NameTrait;

class AssignmentNode extends Node
{
    use NameTrait;
    use AttributeTrait;
}