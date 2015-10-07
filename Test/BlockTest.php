<?php

namespace Tale\Jade\Test;

use Tale\Jade\Renderer;

class BlockTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Compiler */
    private $_renderer;

    public function setUp()
    {

        $this->_renderer = new Renderer([
            'adapter' => 'file',
            'adapterOptions' => [
                'path' => __DIR__.'/cache',
                'lifeTime' => 0
            ],
            'compiler' => [
                'pretty' => false,
                'handleErrors' => false,
                'paths' => [__DIR__.'/views/blocks']
            ]
        ]);
    }

    public function testAppend()
    {

        $this->assertEquals('<p>Element 1</p><p>Element 2</p><p>Element 3</p>', $this->_renderer->render(
            'append'
        ));
    }

    public function testPrepend()
    {

        $this->assertEquals('<p>Element 3</p><p>Element 2</p><p>Element 1</p>', $this->_renderer->render(
            'prepend'
        ));
    }

    public function testReplace()
    {

        $this->assertEquals('<p>Element 4</p><p>Element 5</p><p>Element 6</p>', $this->_renderer->render(
            'replace'
        ));
    }
}