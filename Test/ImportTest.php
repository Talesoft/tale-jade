<?php

namespace Tale\Test\Jade;

use Tale\Jade\Renderer;
use Tale\Jade\Compiler;

class ImportTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Compiler */
    private $_renderer;

    public function setUp()
    {

        $this->_renderer = new Renderer([
            'adapterOptions' => [
                'path' => __DIR__.'/cache/imports'
            ],
            'pretty' => false,
            'paths' => [__DIR__.'/views/imports']
        ]);
    }

    public function testExtends()
    {

        $this->assertEquals('<div><h1>Some Template</h1><p>Passed content to extended file!</p></div>', $this->_renderer->render(
            'extends'
        ));
    }

    public function testInclude()
    {

        $this->assertEquals('<div><h1>Included!</h1><p>I was included, man!</p></div><p><h1>Included!</h1><p>I was included, man!</p></p>', $this->_renderer->render(
            'include'
        ));
    }

    public function testIncludeFilters()
    {

        $this->assertEquals('<style>some, random { css: operations; } and, .just { some: more; }</style><style>some, random { css: operations; } and, .just { some: more; }</style><script>function someJs() { console.log(\'someJs\'); } someJs();</script><script>function someJs() { console.log(\'someJs\'); } someJs();</script>Hello from PHP!Hello from PHP!', $this->_renderer->render(
            'include-filters'
        ));
    }

    public function testFileNotFound()
    {

        $this->setExpectedException(Compiler\Exception::class);

        $this->_renderer->compile('include non-existent-file');
        $this->_renderer->compile('extends non-existent-file');
    }
}