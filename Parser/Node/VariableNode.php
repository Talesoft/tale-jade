<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\Node;
use Tale\Jade\Util\AttributeTrait;
use Tale\Jade\Util\NameTrait;

class VariableNode extends Node
{
    use NameTrait;
    use AttributeTrait;
}