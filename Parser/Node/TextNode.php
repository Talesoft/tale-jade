<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\NodeBase;
use Tale\Jade\Util\EscapeTrait;
use Tale\Jade\Util\LevelTrait;
use Tale\Jade\Util\ValueTrait;

class TextNode extends NodeBase
{
    use ValueTrait;
    use EscapeTrait;
    use LevelTrait;
}