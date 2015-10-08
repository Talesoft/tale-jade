<?php

namespace Tale\Jade\Test;

use Tale\Jade\Compiler;

class AttributeTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Compiler */
    private $_compiler;

    public function setUp()
    {

        $this->_compiler = new Compiler([
            'pretty' => false,
            'handleErrors' => false
        ]);
    }

    public function testNumberValue()
    {

        $this->assertEquals('<a href="some-literal-value"></a>', $this->_compiler->compile('a(href=some-literal-value)'));
    }

    public function testSingleQuotedValue()
    {

        $this->assertEquals('<a href="some value"></a>', $this->_compiler->compile('a(href=\'some value\')'));
    }

    public function testDoubleQuotedValue()
    {

        $this->assertEquals('<a href="some value"></a>', $this->_compiler->compile('a(href="some value")'));
    }

    public function testDoubleColonName()
    {

        $this->assertEquals('<a ns:sub-ns:href="some value"></a>', $this->_compiler->compile('a(ns:sub-ns:href="some value")'));
    }

    public function testLiteralValue()
    {

        $this->assertEquals('<a href="1337"></a>', $this->_compiler->compile('a(href=1337)'));
    }

    public function testSingleVariableExpression()
    {

        $this->assertEquals('<a<?php $__value = isset($url) ? $url : false; if (!\Tale\Jade\Compiler::isNullOrFalse($__value)) echo \' href=\'.\Tale\Jade\Compiler::buildValue($__value, \'"\', true); unset($__value);?>></a>', $this->_compiler->compile('a(href=$url)'));
    }

    public function testRepeation()
    {

        $this->assertEquals('<a href="firstsecond"></a>', $this->_compiler->compile('a(href="first", href=\'second\')'));
    }

    public function testClassRepeation()
    {

        $this->assertEquals('<a class="first second"></a>', $this->_compiler->compile('a(class="first", class=\'second\')'));
    }

    public function testStyleRepeation()
    {

        $this->assertEquals('<a style="first: first-value; second: second-value"></a>', $this->_compiler->compile(
            'a(style="first: first-value", style=\'second: second-value\')'
        ));
    }
}