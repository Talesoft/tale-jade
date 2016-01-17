<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;

class FilterTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Compiler */
    private $_compiler;

    public function setUp()
    {

        $this->_compiler = new Compiler();
    }

    public function testSingleLineJsFilter()
    {

        $this->assertEquals('<script>some.java.script();</script>', $this->_compiler->compile(':js some.java.script();'));
    }

    public function testMultiLineJsFilter()
    {

        $jade = <<<JADE
:js
    some.java.script();
    some.more.java.script();
JADE;

        $this->assertEquals(
            '<script>some.java.script(); some.more.java.script();</script>',
            $this->_compiler->compile($jade)
        );
    }

    public function testSingleLineCssFilter()
    {

        $this->assertEquals('<style>some, random {css: code;}</style>', $this->_compiler->compile(':css some, random {css: code;}'));
    }

    public function testMultiLineCssFilter()
    {

        $jade = <<<JADE
:css
    body, html {
        can-you: imagine;
        that: this;
        works: perfectly;
    }
JADE;

        $this->assertEquals(
            '<style>body, html { can-you: imagine; that: this; works: perfectly; }</style>',
            $this->_compiler->compile($jade)
        );
    }
}