<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler\Exception;
use Tale\Jade\Renderer;

class MixinTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Renderer */
    private $renderer;

    public function setUp()
    {

        $this->renderer = new Renderer([
            'cache_path' => __DIR__.'/cache/mixins',
            'pretty' => false,
            'paths' => [__DIR__.'/views/mixins']
        ]);
    }

    public function testDefinitionAndCall()
    {

        $this->assertEquals('<a href="#">Default Label</a><a href="#">Default Label</a><a href="#">Label 1</a><a href="#">Label 2</a>', $this->renderer->render(
            'definition-and-call', [
            'passedLabel' => 'Label 2'
        ]));
    }

    public function testCompileCalledOnly()
    {

        //TODO: I think this one doesnt make too much sense, does it?
        $this->assertEquals('<p>This mixin was called</p><p>This mixin was called as well</p>', $this->renderer->render(
            'compile-called-only'
        ));
    }

    public function testBlock()
    {

        $this->assertEquals('<h2>Article 1</h2><p><strong>Block Content 1</strong>Awesome, isn\'t it?</p><h2>Article 2</h2><p><strong>Block Content 2</strong>And another block content</p>', $this->renderer->render(
            'block'
        ));
    }

    public function testArguments()
    {

        $this->assertEquals('<a class="1">Some Spacer Content!</a><a class="1.1">Some Spacer Content!</a><a class="abc">Some Spacer Content!</a><a class="abc">Some Spacer Content!</a><a class="a b c">Some Spacer Content!</a><a class="1 a 1.1">Some Spacer Content!</a><a class="ABC">Some Spacer Content!</a><a class="c">Some Spacer Content!</a><a class="1">Some Spacer Content!</a><a>Some Spacer Content!</a><a class="someString">Some Spacer Content!</a><a class="someString">Some Spacer Content!</a><a class="a b c">Some Spacer Content!</a><a class="SOME STRING">Some Spacer Content!</a>', $this->renderer->render(
            'arguments'
        ));
    }

    public function testIdAndClassForwarding()
    {

        $this->assertEquals('<button class="btn btn-default" id="someId">My Button Label</button>', $this->renderer->render(
            'id-and-class-forwarding'
        ));
    }

    public function testVariadic()
    {

        $this->assertEquals('<h1>Test 1</h1><item name="Item 1" id="51"></item><item name="Item 2" id="52"></item><item name="Item 4" id="54"></item><h1>Test 2</h1><item name="Item 5" id="55"></item><item name="Item 6" id="56"></item><item name="Item 7" id="57"></item>', $this->renderer->render('variadic', ['items' => [
            ['name' => 'Item 1', 'id' => 51],
            ['name' => 'Item 2', 'id' => 52],
            ['name' => 'Item 3', 'id' => 53],
            ['name' => 'Item 4', 'id' => 54],
            ['name' => 'Item 5', 'id' => 55],
            ['name' => 'Item 6', 'id' => 56],
            ['name' => 'Item 7', 'id' => 57],
            ['name' => 'Item 8', 'id' => 58],
        ]]));
    }

    public function testScoping()
    {

        $this->assertEquals('<h1>Parent Mixin</h1><div class="parent-block"><h2>Child Mixin</h2><div class="child-block"><span>This is test content</span><span>This content is fed from outside</span></div></div>', $this->renderer->render('scoping', ['fedFromOutside' => 'This content is fed from outside']));
    }

    public function testDuplicateWithoutOverwrite()
    {

        $this->setExpectedException(Exception::class);
        $this->renderer->render('duplicate');
    }

    public function testDuplicateWithOverwrite()
    {

        $this->renderer->getCompiler()->setOption('replace_mixins', true);
        $this->assertEquals('<p>Testing</p>', $this->renderer->render('duplicate'));
    }

    public function testDuplicateInInclude()
    {

        $this->renderer->getCompiler()->setOption('replace_mixins', true);
        $this->assertEquals('<p>Testing</p>', $this->renderer->render('duplicate-in-include'));
    }

    public function testCircular()
    {


        $this->assertEquals('<b>a</b>', $this->renderer->render('circular'));
    }
}