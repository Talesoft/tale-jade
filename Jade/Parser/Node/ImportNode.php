<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\Node;
use Tale\Jade\Util\FilterTrait;
use Tale\Jade\Util\NameTrait;
use Tale\Jade\Util\PathTrait;

class ImportNode extends Node
{
    use NameTrait;
    use PathTrait;
    use FilterTrait;
}