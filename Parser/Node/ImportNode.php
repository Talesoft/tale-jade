<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\NodeBase;
use Tale\Jade\Util\FilterTrait;
use Tale\Jade\Util\NameTrait;
use Tale\Jade\Util\PathTrait;

class ImportNode extends NodeBase
{
    use NameTrait;
    use PathTrait;
    use FilterTrait;
}