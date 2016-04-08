<?php

namespace Tale\Jade\Filter;

use Stylus\Stylus as StylusParser;

class Stylus extends OptimizableFilter
{
    private $stylus;

    public function __construct(StylusParser $stylus = null)
    {
        $this->stylus = $stylus ?: new StylusParser;
    }

    public function optimize(Renderer $renderer, Filter $node, $options)
    {
        $renderer->write($this->filter($this->getContent($node), array(), $options));
    }

    public function filter($content, array $context, $options)
    {
        $css = $stylus->fromString($content)->toString();

        if (isset($options['cdata']) && $options['cdata'] === true) {
            return "<style type=\"text/css\">\n/*<![CDATA[*/\n".$css."\n/*]]>*/\n</style>";
        }

        return "<style type=\"text/css\">\n".$css."\n</style>";
    }
}
