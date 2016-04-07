<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;
use Tale\Jade\Lexer;
use Tale\Jade\Parser;

class AntiTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Compiler */
    private $compiler;

    public function setUp()
    {

        $this->compiler = new Compiler();
    }

    public function testWhenNotCaseChild()
    {
        $this->setExpectedException(Compiler\Exception::class);

        $this->compiler->compile('when "abc"');
    }

    public function testUnclosedAttributeBlockOnElement()
    {
        $this->setExpectedException(Compiler\Exception::class);

        $this->compiler->compile('some-element(abc, def');
    }

    public function testUnclosedAttributeBlockOnMixin()
    {
        $this->setExpectedException(Compiler\Exception::class);

        $this->compiler->compile('mixin some-mixin(abc, def');
    }

    public function testUnclosedAttributeBlockOnMixinCall()
    {
        $this->setExpectedException(Compiler\Exception::class);

        $this->compiler->compile('+some-mixin(abc, def');
    }

    public function testNestedMixin()
    {

        $this->setExpectedException(Compiler\Exception::class);

        $this->compiler->compile("mixin some-mixin()\n\tmixin some-sub-mixin()");
    }

    public function testDoWithoutWhile()
    {
        $this->setExpectedException(Compiler\Exception::class);

        $this->compiler->compile("do\n\tp Something\nnot-a-while-element");
    }

    public function testStandaloneWhile()
    {
        $this->setExpectedException(Compiler\Exception::class);

        $this->compiler->compile("while \$something");
    }

    public function testDoWhileWithWhileChildren()
    {

        $this->setExpectedException(Compiler\Exception::class);

        $this->compiler->compile("do\n\tp Something\nwhile \$something\n\tp Anything");
    }
}