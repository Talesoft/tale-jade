<?php

namespace Tale\Jade\Test;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;

class VariableTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Renderer */
    private $_renderer;

    public function setUp()
    {

        $this->_renderer = new Renderer([
            'adapter' => 'file',
            'adapterOptions' => [
                'path' => __DIR__.'/cache',
                'lifeTime' => 0
            ],
            'compilerOptions' => [
                'pretty' => false,
                'handleErrors' => false,
                'paths' => [__DIR__.'/views/variables']
            ]
        ]);
    }

    public function testAssignment()
    {

        $this->assertEquals('<p>Hello World!</p>THE REAL VALUE222', $this->_renderer->render('assignment', [
            'existing' => ['style' => ['width' => '100%', 'height' => '50%']]]
        ));
    }
}