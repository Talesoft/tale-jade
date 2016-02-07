<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;

class ClassTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Compiler */
    private $_compiler;

    public function setUp()
    {

        $this->_compiler = new Compiler();
    }

    public function testClass()
    {

        $this->assertEquals('<div class="test">Test</div>', $this->_compiler->compile('.test Test'));
    }

    public function testNestedClass()
    {

        $jade = <<<JADE
.test-parent
    .test-child Test
JADE;

        $this->assertEquals('<div class="test-parent"><div class="test-child">Test</div></div>', $this->_compiler->compile($jade));
    }

    public function testTagClassCombination()
    {

        $this->assertEquals('<p class="test">Test</p>', $this->_compiler->compile('p.test Test'));
    }

    public function testNestedTagClassCombination()
    {

        $jade = <<<JADE
div.test-parent
    p.test-child Test
JADE;

        $this->assertEquals('<div class="test-parent"><p class="test-child">Test</p></div>', $this->_compiler->compile($jade));
    }

    public function testComplexTagClassCombination()
    {

        $jade = <<<JADE
.test-container
    nav.test-nav
        ul.test-menu
            li.test-item
                a.test-link
            li.test-item
                a.test-link
            li.test-item
                a.test-link
    nav.test-nav
        ul.test-menu
            li.test-item
                a.test-link
            li.test-item
                a.test-link
            li.test-item
                a.test-link
    nav.test-nav
        ul.test-menu
            li.test-item
                a.test-link
            li.test-item
                a.test-link
            li.test-item
                a.test-link
JADE;

        $this->assertEquals('<div class="test-container"><nav class="test-nav"><ul class="test-menu"><li class="test-item"><a class="test-link"></a></li><li class="test-item"><a class="test-link"></a></li><li class="test-item"><a class="test-link"></a></li></ul></nav><nav class="test-nav"><ul class="test-menu"><li class="test-item"><a class="test-link"></a></li><li class="test-item"><a class="test-link"></a></li><li class="test-item"><a class="test-link"></a></li></ul></nav><nav class="test-nav"><ul class="test-menu"><li class="test-item"><a class="test-link"></a></li><li class="test-item"><a class="test-link"></a></li><li class="test-item"><a class="test-link"></a></li></ul></nav></div>', $this->_compiler->compile($jade));
    }
}