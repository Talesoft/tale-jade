<?php

namespace Tale\Jade\Filter;

use MtHaml\NodeVisitor\RendererAbstract as Renderer;
use CoffeeScript\Compiler;
use Tale\Jade\Parser\Node\FilterNode;

class CoffeeScript extends AbstractFilter
{
    private $coffeescript;
    private $options;

    public function __construct(Compiler $coffeescript = null, array $options = array())
    {
        $this->coffeescript = $coffeescript ?: new Compiler();
        $this->options = $options;
    }

    public function optimize(Renderer $renderer, FilterNode $node, $options)
    {
        $renderer->write($this->filter($this->getContent($node), array(), $options));
    }

    public function filter($content, array $context, $options)
    {
        if (isset($options['cdata']) && $options['cdata'] === true) {
            return "<script type=\"text/javascript\">\n//<![CDATA[\n".$this->coffeescript->compile($content, $this->options)."\n//]]\n</script>";
        }

        return "<script type=\"text/javascript\">\n".$this->coffeescript->compile($content, $this->options)."\n</script>";
    }
}
