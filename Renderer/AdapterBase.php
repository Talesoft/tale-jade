<?php

namespace Tale\Jade\Renderer;

use Tale\Jade\Renderer;

abstract class AdapterBase
{

    private $_renderer;
    private $_options;

    public function __construct(Renderer $renderer, array $options = null)
    {

        $this->_renderer = $renderer;
        $this->_options = $options ? $options : [];
    }

    public function getRenderer()
    {

        return $this->_renderer;
    }

    protected function getOption($name)
    {

        return $this->_options[$name];
    }

    abstract public function render($path, array $args = null);
}