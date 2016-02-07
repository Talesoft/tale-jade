<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\NodeBase;
use Tale\Jade\Util\BlockTrait;
use Tale\Jade\Util\ValueTrait;

class CodeNode extends NodeBase
{
    use ValueTrait;
    use BlockTrait;
}