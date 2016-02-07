<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\NodeBase;
use Tale\Jade\Util\NameTrait;
use Tale\Jade\Util\PairTrait;
use Tale\Jade\Util\SubjectTrait;

class EachNode extends NodeBase
{
    use SubjectTrait;
    use PairTrait;
}