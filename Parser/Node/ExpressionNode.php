<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\Node;
use Tale\Jade\Util\CheckTrait;
use Tale\Jade\Util\EscapeTrait;
use Tale\Jade\Util\ValueTrait;

class ExpressionNode extends Node
{
    use ValueTrait;
    use EscapeTrait;
    use CheckTrait;
}