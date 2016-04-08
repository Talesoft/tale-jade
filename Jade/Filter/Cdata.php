<?php

namespace Tale\Jade\Filter;

use MtHaml\NodeVisitor\RendererAbstract as Renderer;
use Tale\Jade\Parser\Node\FilterNode;

class Cdata extends Plain
{
    public function optimize(Renderer $renderer, FilterNode $node, $options)
    {
        $renderer->write('<![CDATA[')->indent();
        $this->renderFilter($renderer, $node);
        $renderer->undent()->write(']]>');
    }
}
