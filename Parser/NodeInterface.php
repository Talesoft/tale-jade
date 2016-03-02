<?php

namespace Tale\Jade\Parser;

use Tale\Tree\NodeInterface as TreeNodeInterface;

interface NodeInterface extends TreeNodeInterface
{

    public function getLine();
    public function getOffset();
    public function getOuterNode();
    public function setOuterNode(Node $node);
}