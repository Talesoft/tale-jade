<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\Node;
use Tale\Jade\Util\BlockTrait;
use Tale\Jade\Util\ValueTrait;

class CodeNode extends Node
{
    use ValueTrait;
    use BlockTrait;
}