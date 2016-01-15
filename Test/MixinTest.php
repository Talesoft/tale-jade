<?php

namespace Tale\Jade\Test;

use Tale\Jade\Renderer;

class MixinTest extends \PHPUnit_Framework_TestCase
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
                'paths' => [__DIR__.'/views/mixins']
            ]
        ]);
    }

    public function testDefinitionAndCall()
    {

        $this->assertEquals('<a href="#">Default Label</a><a href="#">Default Label</a><a href="#">Label 1</a><a href="#">Label 2</a>', $this->_renderer->render(
            'definition-and-call', [
            'passedLabel' => 'Label 2'
        ]));
    }

    public function testCompileCalledOnly()
    {

        //TODO: I think this one doesnt make too much sense, does it?
        $this->assertEquals('<p>This mixin was called</p><p>This mixin was called as well</p>', $this->_renderer->render(
            'compile-called-only'
        ));
    }

    public function testBlock()
    {

        $this->assertEquals('<h2>Article 1</h2><p><strong>Block Content 1</strong> Awesome, isn\'t it?</p><h2>Article 2</h2><p><strong>Block Content 2</strong> And another block content</p>', $this->_renderer->render(
            'block'
        ));
    }

    public function testArguments()
    {

        $this->assertEquals('<a class="1">Some Spacer Content!</a><a class="1.1">Some Spacer Content!</a><a class="abc">Some Spacer Content!</a><a class="abc">Some Spacer Content!</a><a class="a b c">Some Spacer Content!</a><a class="1 a 1.1">Some Spacer Content!</a><a class="ABC">Some Spacer Content!</a><a class="c">Some Spacer Content!</a><a class="1">Some Spacer Content!</a><a>Some Spacer Content!</a><a class="someString">Some Spacer Content!</a><a class="someString">Some Spacer Content!</a><a class="a b c">Some Spacer Content!</a>', $this->_renderer->render(
            'arguments'
        ));
    }

    public function testIdAndClassForwarding()
    {

        $this->assertEquals('<button class="btn btn-default" id="someId">My Button Label</button>', $this->_renderer->render(
            'id-and-class-forwarding'
        ));
    }
}