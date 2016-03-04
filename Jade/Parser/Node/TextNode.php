<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\Node;
use Tale\Jade\Util\EscapeTrait;
use Tale\Jade\Util\LevelTrait;
use Tale\Jade\Util\ValueTrait;

class TextNode extends Node
{
    use ValueTrait;
    use EscapeTrait;
    use LevelTrait;
}