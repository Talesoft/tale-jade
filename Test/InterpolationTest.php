<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;

class InterpolationTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Renderer */
    private $renderer;

    public function setUp()
    {

        $this->renderer = new Renderer([
            'adapter_options' => [
                'path' => __DIR__.'/cache/interpolation'
            ],
            'pretty' => false,
            'paths' => [__DIR__.'/views/interpolation']
        ]);
    }

    /**
     * @dataProvider expressionProvider
     */
    public function testExpressionInterpolation($prefix, $expression, $expected)
    {

        $this->assertEquals($expected, $this->renderer->compile('p Some Text '.$prefix.'{'.$expression.'}'));
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

        $this->assertEquals('<p>Some Text <a>Some link</a></p>', $this->renderer->compile('p Some Text #[a Some link]'));
        $this->assertEquals('<p>Some Text <?=htmlentities(\'<a>Some link</a>\', \ENT_QUOTES, \'UTF-8\')?></p>', $this->renderer->compile('p Some Text ![a Some link]'));
    }

    public function testNestedInterpolation()
    {

        $this->assertEquals('<p>A <p>B <p>C <p>D</p></p></p></p>', $this->renderer->compile('p A #[p B #[p C #[p D]]]'));
        $this->assertEquals('<p>A <?=htmlentities(isset($b) ? $b : \'\', \ENT_QUOTES, \'UTF-8\')?> <p>C <?=strtoupper($d)?></p></p>', $this->renderer->compile('p A #{$b} #[p C !{strtoupper($d)}]'));
    }

    public function testMultipleInterpolation()
    {

        $this->assertEquals('<p>Some text <?=htmlentities(isset($var) ? $var : \'\', \ENT_QUOTES, \'UTF-8\')?> <p>A</p> <?=htmlentities(strtolower($var), \ENT_QUOTES, \'UTF-8\')?> <?=htmlentities(isset($otherVar) ? $otherVar : \'\', \ENT_QUOTES, \'UTF-8\')?> <?=isset($otherVar) ? $otherVar : \'\'?></p>', $this->renderer->compile("p.\n\tSome text #{\$var} #[p A] #{strtolower(\$var)} #{\$otherVar} !{\$otherVar}"));
        $this->assertEquals('<p>Some text <a>A</a> <p>B</p> <?=htmlentities(isset($var) ? $var : \'\', \ENT_QUOTES, \'UTF-8\')?> <c class="d">E</c> <?=isset($otherVar) ? $otherVar : \'\'?> <f></f></p>', $this->renderer->compile("p.\n\tSome text #[a A] #[p B] #{\$var} #[c.d E] !{\$otherVar} #[f]"));
    }

    public function testInvalidInterpolation()
    {

        $this->setExpectedException(Compiler\Exception::class);

        $this->renderer->compile('#{p Some content');
    }

    public function testInvalidJadeInterpolation()
    {

        $this->setExpectedException(Compiler\Exception::class);

        $this->renderer->compile('#[p Some content');
    }

    public function testMailToLink()
    {

        $this->assertEquals('<div class="copyright">Copyright (c) 2016 <a href="mailto:tk@talesoft.io">tk@talesoft.io</a> Berlin</div>', $this->renderer->compile('.copyright Copyright (c) 2016 #[a(href=\'mailto:tk@talesoft.io\') tk@talesoft.io] Berlin'));
    }

    public function testIeConditionals()
    {

        $this->assertEquals('<!--[if IE8]><script src="ie8.js"></script><![endif]-->', $this->renderer->compile("<!--[if IE8]>\nscript(src='ie8.js')\n<![endif]-->"));
    }

    public function testTagInterpolation()
    {

        $this->assertEquals('<?php $tagName = \'abcdefghi\'?><<?=htmlentities(isset($tagName) ? $tagName : \'\', \ENT_QUOTES, \'UTF-8\')?> class="some-class">Some Content</<?=htmlentities(isset($tagName) ? $tagName : \'\', \ENT_QUOTES, \'UTF-8\')?>>', $this->renderer->compile("\$tagName='abcdefghi'\n#{\$tagName}.some-class Some Content"));
        $this->assertEquals('<?php $tagName = \'def\'?><abc<?=htmlentities(isset($tagName) ? $tagName : \'\', \ENT_QUOTES, \'UTF-8\')?>ghi class="some-class">Some Content</abc<?=htmlentities(isset($tagName) ? $tagName : \'\', \ENT_QUOTES, \'UTF-8\')?>ghi>', $this->renderer->compile("\$tagName='def'\nabc#{\$tagName}ghi.some-class Some Content"));

    }
}