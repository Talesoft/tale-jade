<?php

namespace Tale\Jade\Test;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;

class StandAloneTest extends \PHPUnit_Framework_TestCase
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
                'paths' => [__DIR__.'/views/stand-alone'],
                'standAlone' => true
            ]
        ]);
    }

    public function testStandAloneCompilation()
    {

        $this->assertEquals('<p class="a b c d e f">Test!</p>', $this->_renderer->render('basic', ['classes' => ['e', 'f']]));
    }
}