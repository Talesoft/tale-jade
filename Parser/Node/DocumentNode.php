<?php

namespace Tale\Jade\Parser\Node;

use Tale\Jade\Parser\Node;
use Tale\Jade\Parser\NodeInterface;

class DocumentNode extends Node
{

    private $blocks;
    private $imports;
    private $mixins;
    private $mixinCalls;

    /**
     * Creates a new, detached node without children or a parent.
     *
     * It can be appended to any node after that
     *
     * The type can be any kind of string
     *
     * @param int|null        $line     the line at which we found this node
     * @param int|null        $offset   the offset in a line we found this node at
     * @param int|null        $level    the level of indentation this node is at
     * @param NodeInterface   $parent   the parent of this node
     * @param NodeInterface[] $children the children of this node
     */
    public function __construct($line, $offset, $level, NodeInterface $parent, $children)
    {
        parent::__construct($line, $offset, $level, $parent, $children);

        $this->blocks = [];
        $this->imports = [];
        $this->mixins = [];
        $this->mixinCalls = [];
    }

    public function addBlock(BlockNode $node)
    {

        $this->blocks[] = $node;

        return $this;
    }

    public function addImport(ImportNode $node)
    {

        $this->imports[] = $node;

        return $this;
    }

    public function addMixin(MixinNode $node)
    {

        $this->mixins[] = $node;

        return $this;
    }

    public function addMixinCall(MixinCallNode $node)
    {

        $this->mixinCalls[] = $node;

        return $this;
    }
}