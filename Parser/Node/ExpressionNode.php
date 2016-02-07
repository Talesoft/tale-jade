<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\NodeBase;
use Tale\Jade\Util\CheckTrait;
use Tale\Jade\Util\EscapeTrait;
use Tale\Jade\Util\ValueTrait;

class ExpressionNode extends NodeBase
{
    use ValueTrait;
    use EscapeTrait;
    use CheckTrait;
}