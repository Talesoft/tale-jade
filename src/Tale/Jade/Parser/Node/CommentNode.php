<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\NodeBase;
use Tale\Jade\Util\EscapeTrait;
use Tale\Jade\Util\RenderTrait;

class CommentNode extends NodeBase
{
    use RenderTrait;
}