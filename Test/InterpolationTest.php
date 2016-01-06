<?php

namespace Tale\Jade\Test;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;

class InterpolationTest extends \PHPUnit_Framework_TestCase
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
                'paths' => [__DIR__.'/views/interpolation']
            ]
        ]);
    }

    /**
     * @dataProvider expressionProvider
     */
    public function testExpressionInterpolation($prefix, $expression, $expected)
    {

        $this->assertEquals($expected, $this->_renderer->compile('p Some Text '.$prefix.'{'.$expression.'}'));
    }

    public function expressionProvider()
    {

        return [
            ['#', '$someVar', '<p>Some Text <?=htmlentities(isset($someVar) ? $someVar : \'\', \ENT_QUOTES, \'UTF-8\')?></p>'],
            ['#', '$some["var"]', '<p>Some Text <?=htmlentities(isset($some["var"]) ? $some["var"] : \'\', \ENT_QUOTES, \'UTF-8\')?></p>'],
            ['#', '$some[\'var\']', '<p>Some Text <?=htmlentities(isset($some[\'var\']) ? $some[\'var\'] : \'\', \ENT_QUOTES, \'UTF-8\')?></p>'],
            ['#', '$some->var', '<p>Some Text <?=htmlentities(isset($some->var) ? $some->var : \'\', \ENT_QUOTES, \'UTF-8\')?></p>'],
            ['#', '$some["var"][\'var\']', '<p>Some Text <?=htmlentities(isset($some["var"][\'var\']) ? $some["var"][\'var\'] : \'\', \ENT_QUOTES, \'UTF-8\')?></p>'],
            ['#', '$some[\'var\']["var"]', '<p>Some Text <?=htmlentities(isset($some[\'var\']["var"]) ? $some[\'var\']["var"] : \'\', \ENT_QUOTES, \'UTF-8\')?></p>'],
            ['#', '$some->var->var', '<p>Some Text <?=htmlentities(isset($some->var->var) ? $some->var->var : \'\', \ENT_QUOTES, \'UTF-8\')?></p>'],
            ['#', 'strtoupper($var)', '<p>Some Text <?=htmlentities(strtoupper($var), \ENT_QUOTES, \'UTF-8\')?></p>'],
            ['#', '$var->toUpper()', '<p>Some Text <?=htmlentities($var->toUpper(), \ENT_QUOTES, \'UTF-8\')?></p>'],
            ['#', 'strtoupper($var->toLower())', '<p>Some Text <?=htmlentities(strtoupper($var->toLower()), \ENT_QUOTES, \'UTF-8\')?></p>'],
            ['!', '$some["var"][\'var\']', '<p>Some Text <?=isset($some["var"][\'var\']) ? $some["var"][\'var\'] : \'\'?></p>'],
            ['!', '$some[\'var\']["var"]', '<p>Some Text <?=isset($some[\'var\']["var"]) ? $some[\'var\']["var"] : \'\'?></p>'],
            ['!', '$some->var->var', '<p>Some Text <?=isset($some->var->var) ? $some->var->var : \'\'?></p>'],
            ['!', 'strtoupper($var)', '<p>Some Text <?=strtoupper($var)?></p>'],

        ];
    }

    public function testJadeInterpolation()
    {

        $this->assertEquals('<p>Some Text <a>Some link</a></p>', $this->_renderer->compile('p Some Text #[a Some link]'));
    }
}