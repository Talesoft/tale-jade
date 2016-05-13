<?php

namespace Tale\Test\Jade;

use Tale\Jade\Renderer;

class BlockTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Compiler */
    private $renderer;

    public function setUp()
    {

        $this->renderer = new Renderer([
            'adapter_options' => [
                'path' => __DIR__.'/cache/blocks'
            ],
            'pretty' => false,
            'paths' => [__DIR__.'/views/blocks']
        ]);
    }

    public function testAppend()
    {

        $this->assertEquals('<p>Element 1</p><p>Element 2</p><p>Element 3</p>', $this->renderer->render(
            'append'
        ));
    }

    public function testPrepend()
    {

        $this->assertEquals('<p>Element 3</p><p>Element 2</p><p>Element 1</p>', $this->renderer->render(
            'prepend'
        ));
    }

    public function testReplace()
    {

        $this->assertEquals('<p>Element 4</p><p>Element 5</p><p>Element 6</p>', $this->renderer->render(
            'replace'
        ));
    }
}