<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\NodeBase;
use Tale\Jade\Util\AttributesTrait;
use Tale\Jade\Util\FilterTrait;
use Tale\Jade\Util\NameTrait;
use Tale\Jade\Util\PathTrait;
use Tale\Jade\Util\TypeTrait;

class ImportNode extends NodeBase
{

    use TypeTrait;
    use PathTrait;
    use FilterTrait;
    use AttributesTrait;
}