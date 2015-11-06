<?php

namespace Tale\Jade\Test;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;

class MixedTest extends \PHPUnit_Framework_TestCase
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
                'paths' => [__DIR__.'/views/mixed']
            ]
        ]);
    }

    /**
     * @dataProvider attributeValueProvider
     */
    public function testAttributeValues($value, $expected)
    {

        $this->assertEquals($expected, $this->_renderer->render(
            'single-attribute-value',
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
}