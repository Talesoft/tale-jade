<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;
use Tale\Jade\Parser;

class AttributeTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Renderer */
    private $renderer;

    public function setUp()
    {

        $this->renderer = new Renderer([
            'adapter_options' => [
                'path' => __DIR__.'/cache/attributes'
            ],
            'compiler_options' => [
                'echo_xml_doctype' => false
            ],
            'pretty' => false,
            'paths' => [__DIR__.'/views/attributes']
        ]);
    }

    public function testNumberValue()
    {

        $this->assertEquals('<a href="some-literal-value"></a>', $this->renderer->compile('a(href=some-literal-value)'));
    }

    public function testSingleQuotedValue()
    {

        $this->assertEquals('<a href="some value"></a>', $this->renderer->compile('a(href=\'some value\')'));
    }

    public function testDoubleQuotedValue()
    {

        $this->assertEquals('<a href="some value"></a>', $this->renderer->compile('a(href="some value")'));
    }

    public function testDoubleColonName()
    {

        $this->assertEquals('<a ns:sub-ns:href="some value"></a>', $this->renderer->compile('a(ns:sub-ns:href="some value")'));
    }

    public function testLiteralValue()
    {

        $this->assertEquals('<a href="1337"></a>', $this->renderer->compile('a(href=1337)'));
    }

    public function testSingleVariableExpression()
    {

        $this->assertEquals(
            '<a<?php $__value = isset($url) ? $url : false; if (!\Tale\Jade\Compiler\is_null_or_false($__value)) echo \' href=\'.\Tale\Jade\Compiler\build_value($__value, \'"\', true); unset($__value);?>></a>',
            $this->renderer->compile('a(href=$url)')
        );
    }

    public function testCrossAssignment()
    {

        $this->assertEquals(
            '<a href="1234"></a><div class="first second third fourth fifth sixth"></div>',
            $this->renderer->render('cross-assignments', [
                'externAttrs' => [
                    'class' => ['second', 'third', ['fourth', 'fifth']],
                    'style' => [
                        'height' => '50%',
                        'font-size' => '3em'
                    ],
                    'hidden' => null,
                    'visible' => true
                ]
            ])
        );
    }

    public function testRepeation()
    {

        $this->assertEquals('<a href="firstsecond"></a>', $this->renderer->compile('a(href="first", href=\'second\')'));
    }

    public function testClassRepeation()
    {

        $this->assertEquals('<a class="first second"></a>', $this->renderer->compile('a(class="first", class=\'second\')'));
    }

    public function testStyleRepeation()
    {

        $this->assertEquals('<a style="first: first-value; second: second-value"></a>', $this->renderer->compile(
            'a(style="first: first-value", style=\'second: second-value\')'
        ));
    }

    /**
     * @dataProvider attributeValueProvider
     */
    public function testAttributeValues($value, $expected)
    {

        $this->assertEquals($expected, $this->renderer->render(
            'single-value',
            ['value' => $value]
        ));
    }

    public function attributeValueProvider()
    {

        return [
            [1, '<a href="1"></a>'],
            [4.5, '<a href="4.5"></a>'],
            ['test', '<a href="test"></a>'],
            ['?"/\":\'&', '<a href="?&quot;/\&quot;:&#039;&amp;"></a>'],
            [false, '<a></a>'],
            [null, '<a></a>'],
            [true, '<a href="1"></a>'],
            [['one', 'two', 'three'], '<a href="onetwothree"></a>'],
            [(object)['one', 'two', 'three'], '<a href="onetwothree"></a>']
        ];
    }

    public function testUnnamedAttributed()
    {

        $this->setExpectedException(Compiler\Exception::class);
        $this->assertEquals('', $this->renderer->compile('a(="some value")'));
    }

    public function testExpectedButNotGivenValue()
    {

        $this->assertEquals('<!DOCTYPE html><a href></a>', $this->renderer->compile("doctype html\na(href=)"));
        $this->assertEquals('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><a href=""></a>', $this->renderer->compile("doctype default\na(href=)"));
        $this->assertEquals('<?xml version="1.0" encoding="utf-8"?><a href="" />', $this->renderer->compile("doctype xml\na(href=)"));
    }

    public function testSpaceSeparated()
    {

        $this->assertEquals('<meta name="viewport" content="some viewport content"><a href="google.de" target="_blank" title="Some link title"></a>', $this->renderer->render(
            'space-separated'
        ));
    }
}