<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;
use Tale\Jade\Lexer;
use Tale\Jade\Parser;

class AntiTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Compiler */
    private $_compiler;

    public function setUp()
    {

        $this->_compiler = new Compiler();
    }

    public function testWhenNotCaseChild()
    {
        $this->setExpectedException(Compiler\Exception::class);

        $this->_compiler->compile('when "abc"');
    }

    public function testUnclosedAttributeBlockOnElement()
    {
        $this->setExpectedException(Compiler\Exception::class);

        $this->_compiler->compile('some-element(abc, def');
    }

    public function testUnclosedAttributeBlockOnMixin()
    {
        $this->setExpectedException(Compiler\Exception::class);

        $this->_compiler->compile('mixin some-mixin(abc, def');
    }

    public function testUnclosedAttributeBlockOnMixinCall()
    {
        $this->setExpectedException(Compiler\Exception::class);

        $this->_compiler->compile('+some-mixin(abc, def');
    }

    public function testNestedMixin()
    {

        $this->setExpectedException(Compiler\Exception::class);

        $this->_compiler->compile("mixin some-mixin()\n\tmixin some-sub-mixin()");
    }

    public function testDoWithoutWhile()
    {
        $this->setExpectedException(Compiler\Exception::class);

        $this->_compiler->compile("do\n\tp Something\nnot-a-while-element");
    }

    public function testStandaloneWhile()
    {
        $this->setExpectedException(Compiler\Exception::class);

        $this->_compiler->compile("while \$something");
    }

    public function testDoWhileWithWhileChildren()
    {

        $this->setExpectedException(Compiler\Exception::class);

        $this->_compiler->compile("do\n\tp Something\nwhile \$something\n\tp Anything");
    }
}