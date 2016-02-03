<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;

class EscapingTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Renderer */
    private $_renderer;

    public function setUp()
    {

        $this->_renderer = new Renderer([
            'adapterOptions' => [
                'path' => __DIR__.'/cache/escaping'
            ],
            'pretty' => false,
            'paths' => [__DIR__.'/views/escaping']
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

    public function testUncheckedExpressionCompilation()
    {

        $this->assertEquals('<?=htmlentities($someExpression, \ENT_QUOTES, \'UTF-8\')?>', $this->_renderer->compile('?= $someExpression'));
        $this->assertEquals('<div><p><?=htmlentities($someExpression, \ENT_QUOTES, \'UTF-8\')?></p></div>', $this->_renderer->compile("div\n\tp?= \$someExpression"));
    }

    public function testUncheckedUnescapedExpressionCompilation()
    {

        $this->assertEquals('<?=$someExpression?>', $this->_renderer->compile('?!= $someExpression'));
        $this->assertEquals('<div><p><?=$someExpression?></p></div>', $this->_renderer->compile("div\n\tp?!= \$someExpression"));
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

    public function testUncheckedInterpolationCompilation()
    {

        $this->assertEquals('<p>Some <?=htmlentities($someVar, \ENT_QUOTES, \'UTF-8\')?> random text</p>', $this->_renderer->compile('p Some ?#{$someVar} random text'));
    }

    public function testUncheckedUnescapedInterpolationCompilation()
    {

        $this->assertEquals('<p>Some <?=$someVar?> random text</p>', $this->_renderer->compile('p Some ?!{$someVar} random text'));
    }

    public function testEscapedAttributeCompilation()
    {

        $this->assertEquals('<a<?php $__value = isset($url) ? $url : false; if (!\Tale\Jade\Compiler\is_null_or_false($__value)) echo \' href=\'.\Tale\Jade\Compiler\build_value($__value, \'"\', true); unset($__value);?>></a>', $this->_renderer->compile('a(href=$url)'));
    }

    public function testUnescapedAttributeCompilation()
    {

        $this->assertEquals('<a<?php $__value = isset($url) ? $url : false; if (!\Tale\Jade\Compiler\is_null_or_false($__value)) echo \' href=\'.\Tale\Jade\Compiler\build_value($__value, \'"\', false); unset($__value);?>></a>', $this->_renderer->compile('a(href!=$url)'));
    }

    public function testUncheckedAttributeCompilation()
    {

        $this->assertEquals('<a<?php $__value = $url; if (!\Tale\Jade\Compiler\is_null_or_false($__value)) echo \' href=\'.\Tale\Jade\Compiler\build_value($__value, \'"\', true); unset($__value);?>></a>', $this->_renderer->compile('a(href?=$url)'));
    }

    public function testUncheckedUnescapedCompilation()
    {

        $this->assertEquals('<a<?php $__value = $url; if (!\Tale\Jade\Compiler\is_null_or_false($__value)) echo \' href=\'.\Tale\Jade\Compiler\build_value($__value, \'"\', false); unset($__value);?>></a>', $this->_renderer->compile('a(href?!=$url)'));
    }

    public function testUnescapedText()
    {

        $this->assertEquals('Some <b>Text</b>', $this->_renderer->compile('| Some <b>Text</b>'));
        $this->assertEquals('<div>Some <b>Text</b></div>', $this->_renderer->compile('div Some <b>Text</b>'));
        $this->assertEquals('<p>Some <b>Text</b> Some <i>further</i> text</p>', $this->_renderer->compile("p.\n\tSome <b>Text</b>\n\tSome <i>further</i> text"));
    }

    public function testEscapedText()
    {

        $this->assertEquals('<?=htmlentities(\'Some <b>Text</b>\', \ENT_QUOTES, \'UTF-8\')?>', $this->_renderer->compile('!| Some <b>Text</b>'));
        $this->assertEquals('<div><?=htmlentities(\'Some <b>Text</b>\', \ENT_QUOTES, \'UTF-8\')?></div>', $this->_renderer->compile('div! Some <b>Text</b>'));
        $this->assertEquals('<p><?=htmlentities(\'Some <b>Text</b>\', \ENT_QUOTES, \'UTF-8\')?> <?=htmlentities(\'Some <i>further</i> text\', \ENT_QUOTES, \'UTF-8\')?></p>', $this->_renderer->compile("p!.\n\tSome <b>Text</b>\n\tSome <i>further</i> text"));
    }

    public function testInterpolationInEscapedText()
    {

        $this->assertEquals('<p><?=htmlentities(\'This is some text \'.(isset($var[\'some var\']) ? $var[\'some var\'] : \'\').\' <a href="abc"></a>\', \ENT_QUOTES, \'UTF-8\')?></p>', $this->_renderer->compile('p! This is some text !{$var[\'some var\']} #[a(href!=\'abc\')]'));
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