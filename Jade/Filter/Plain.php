<?php

namespace Tale\Jade\Filter;

use MtHaml\NodeVisitor\RendererAbstract as Renderer;
use Tale\Jade\Parser\Node\FilterNode;

class Plain extends AbstractFilter
{
    public function isOptimizable(Renderer $renderer, FilterNode $node, $options)
    {
        return true;
    }

    public function optimize(Renderer $renderer, FilterNode $filter, $options)
    {
        $this->renderFilter($renderer, $filter);
    }

    public function filter($content, array $context, $options)
    {
        throw new \RuntimeException('Filter is optimizable and does not run in runtime');
    }
}
