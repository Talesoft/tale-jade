<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\Node;
use Tale\Jade\Util\NameTrait;
use Tale\Jade\Util\PairTrait;
use Tale\Jade\Util\SubjectTrait;

class EachNode extends Node
{
    use SubjectTrait;
    use PairTrait;
}