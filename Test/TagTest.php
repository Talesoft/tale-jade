<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;

class TagTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Compiler */
    private $compiler;

    public function setUp()
    {

        $this->compiler = new Compiler();
    }

    public function testTag()
    {

        $this->assertEquals('<p>Test</p>', $this->compiler->compile('p Test'));
    }

    public function testTagChars()
    {

        $this->assertEquals(
            '<abcdefghijklmnopqrstuvwxyz-_ABCDEFGHIJKLMNOPQRSTUVWXYZ>Test</abcdefghijklmnopqrstuvwxyz-_ABCDEFGHIJKLMNOPQRSTUVWXYZ>',
            $this->compiler->compile('abcdefghijklmnopqrstuvwxyz-_ABCDEFGHIJKLMNOPQRSTUVWXYZ Test')
        );
    }

    public function testNamespacedTag()
    {

        $this->assertEquals(
            '<a:b>Test</a:b>',
            $this->compiler->compile('a:b Test')
        );

        $this->assertEquals(
            '<a-b:c-d>Test</a-b:c-d>',
            $this->compiler->compile('a-b:c-d Test')
        );
    }

    public function testNestedTag()
    {

        $jade = <<<JADE
p
    a Test
JADE;

        $this->assertEquals('<p><a>Test</a></p>', $this->compiler->compile($jade));
    }

    public function testTabTags()
    {

        $jade = <<<JADE
doctype html
head
\ttitle
\tlink
body
\th1
\tdiv
\t\tp Some text
\t\ta Some link
\tscript
JADE;

        $this->assertEquals('<!DOCTYPE html><head><title></title><link></head><body><h1></h1><div><p>Some text</p><a>Some link</a></div><script></script></body>', $this->compiler->compile($jade));
    }

    public function testComplexNestedTag()
    {

        $jade = <<<JADE
div
    nav
        ul
            li
                a
            li
                a
            li
                a
    nav
        ul
            li
                a
            li
                a
            li
                a
    nav
        ul
            li
                a
            li
                a
            li
                a
JADE;

        $this->assertEquals('<div><nav><ul><li><a></a></li><li><a></a></li><li><a></a></li></ul></nav><nav><ul><li><a></a></li><li><a></a></li><li><a></a></li></ul></nav><nav><ul><li><a></a></li><li><a></a></li><li><a></a></li></ul></nav></div>', $this->compiler->compile($jade));
    }
}