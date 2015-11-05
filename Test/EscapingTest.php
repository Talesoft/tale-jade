<?php

namespace Tale\Jade\Test;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;

class EscapingTest extends \PHPUnit_Framework_TestCase
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
                'paths' => [__DIR__.'/views/escaping']
            ]
        ]);
    }

    public function testEscapedExpressionCompilation()
    {

        $this->assertEquals('<?=htmlentities(isset($someExpression) ? $someExpression : \'\', \ENT_QUOTES, \'UTF-8\')?>', $this->_renderer->compile('= $someExpression'));
    }

    public function testEscapedExpressionRendering()
    {

        $this->assertEquals('<p>&lt;a href=&quot;#&quot;&gt;&#039; some random text &amp;&lt;/a&gt;</p>', $this->_renderer->render('escaped-expression', ['expression' => '<a href="#">\' some random text &</a>']));
    }

    public function testUnescapedExpression()
    {

        $this->assertEquals('<?=isset($someExpression) ? $someExpression : \'\'?>', $this->_renderer->compile('!= $someExpression'));
    }

    public function testUnecapedExpressionRendering()
    {

        $this->assertEquals('<p><a href="#">\' some random text &</a></p>', $this->_renderer->render('unescaped-expression', ['expression' => '<a href="#">\' some random text &</a>']));
    }

    public function testNewLineEscaping()
    {
        $this->assertEquals("<input value=\"Line 1\nLine 2\">", $this->_renderer->compile('input(value="Line 1\nLine 2")'));
    }

    public function testTabEscaping()
    {
        $this->assertEquals("<input value=\"Line 1\tLine 2\">", $this->_renderer->compile('input(value="Line 1\tLine 2")'));
    }

    public function testCarriageReturnEscaping()
    {
        $this->assertEquals("<input value=\"Line 1\rLine 2\">", $this->_renderer->compile('input(value="Line 1\rLine 2")'));
    }
}