<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;

class VariableTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Renderer */
    private $renderer;

    public function setUp()
    {

        $this->renderer = new Renderer([
            'adapter_options' => [
                'path' => __DIR__.'/cache/variables'
            ],
            'pretty' => false,
            'paths' => [__DIR__.'/views/variables']
        ]);
    }

    public function testAssignment()
    {

        $this->assertEquals('<p>Hello World!</p>nowrap212<div style="width: 100%; height: 50%"></div>', $this->renderer->render('assignment', [
            'existing' => ['style' => ['width' => '100%'], 'class' => 'test']
        ]));
    }
}