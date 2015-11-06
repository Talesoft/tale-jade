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

        $this->assertEquals(
            '<p>&lt;a href=&quot;#&quot;&gt;&#039; some random text &amp;&lt;/a&gt;</p>',
            $this->_renderer->render('escaped-expression', ['expression' => '<a href="#">\' some random text &</a>'])
        );
    }

    public function testUnescapedExpressionCompilation()
    {

        $this->assertEquals(
            '<?=isset($someExpression) ? $someExpression : \'\'?>',
            $this->_renderer->compile('!= $someExpression')
        );
    }

    public function testUnescapedExpressionRendering()
    {

        $this->assertEquals('<p><a href="#">\' some random text &</a></p>', $this->_renderer->render('unescaped-expression', ['expression' => '<a href="#">\' some random text &</a>']));
    }

    public function testEscapedInterpolationCompilation()
    {

        $this->assertEquals('<p>Some <?=htmlentities(isset($someVar) ? $someVar : \'\', \ENT_QUOTES, \'UTF-8\')?> random text</p>', $this->_renderer->compile('p Some #{$someVar} random text'));
    }

    public function testEscapedInterpolationRendering()
    {

        $this->assertEquals('<p>In this random text i will insert an &lt;a href=&quot;#&quot;&gt;&#039; some random text &amp;&lt;/a&gt;, awesome, isn\'t it?</p>', $this->_renderer->render('escaped-interpolation', ['expression' => '<a href="#">\' some random text &</a>']));
    }

    public function testUnescapedInterpolationRendering()
    {

        $this->assertEquals('<p>In this random text i will insert an <a href="#">\' some random text &</a>, awesome, isn\'t it?</p>', $this->_renderer->render('unescaped-interpolation', ['expression' => '<a href="#">\' some random text &</a>']));
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