<?php

namespace Tale\Jade\Renderer\Adapter;

use Tale\Jade\Renderer;
use Tale\Jade\Renderer\Adapter\Stream\Wrapper;
use Tale\Jade\Renderer\AdapterBase;

class Stream extends AdapterBase
{

    public function __construct(Renderer $renderer, array $options = null)
    {

        parent::__construct($renderer, array_replace_recursive([
            'scheme' => 'jade-phtml'
        ], $options ? $options : []));

        if (!Wrapper::isRegistered($this->getOption('scheme')))
            Wrapper::register($this->getOption('scheme'));
    }

    public function render($path, array $args = null)
    {

        $compiled = base64_encode($this->getRenderer()->compileFile($path));
        $url = $this->getOption('scheme').'://data;'.$compiled;


        $render = function($__dataUrl, $__args) {

            ob_start();
            extract($__args);
            include($__dataUrl);

            return ob_get_clean();
        };

        return $render($url, $args ? $args : []);
    }
}