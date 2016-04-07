<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;
use Tale\Jade\Lexer;

class BlockExpansionTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Compiler */
    private $compiler;

    public function setUp()
    {

        $this->compiler = new Compiler();
    }

    public function testTagExansion()
    {
        $this->assertEquals('<a><b><c><d><e></e></d></c></b></a>', $this->compiler->compile('a: b: c: d: e'));
    }

    public function testClassExpansion()
    {
        $this->assertEquals('<div class="a"><div class="b"><div class="c"><div class="d"><div class="e"></div></div></div></div></div>', $this->compiler->compile('.a: .b: .c: .d: .e'));
    }

    public function testIdExpansion()
    {
        $this->assertEquals('<div id="a"><div id="b"><div id="c"><div id="d"><div id="e"></div></div></div></div></div>', $this->compiler->compile('#a: #b: #c: #d: #e'));
    }

    public function testMixedExpansion()
    {
        $this->assertEquals('<nav><ul class="main-menu"><li class="active"><a id="start" href="start"></a></li></ul></nav>', $this->compiler->compile('nav: ul.main-menu: li.active: a#start(href="start")'));
    }

    public function testIfExpansion()
    {
        $this->assertEquals('<?php if (isset($someVar) ? $someVar : false) {?><p>Hello <?=htmlentities(isset($someVar) ? $someVar : \'\', \ENT_QUOTES, \'UTF-8\')?>!</p><?php }?>', $this->compiler->compile('if $someVar: p Hello #{$someVar}!'));
        $this->assertEquals('<?php if (isset($someVar) ? $someVar : \'abc\') {?><p>Hello <?=htmlentities(isset($someVar) ? $someVar : \'\', \ENT_QUOTES, \'UTF-8\')?>!</p><?php }?>', $this->compiler->compile('if (isset($someVar) ? $someVar : \'abc\'): p Hello #{$someVar}!'));
    }

    public function testComplexExpansion()
    {

        $jade = <<<JADE
a: b: c
    d: .e: #f
a: b: c(aa='bb'): d
    d: e
        f: g: h
    d: e
        f: g: h
JADE;

        $this->assertEquals('<a><b><c><d><div class="e"><div id="f"></div></div></d></c></b></a><a><b><c aa="bb"><d><d><e><f><g><h></h></g></f></e></d><d><e><f><g><h></h></g></f></e></d></d></c></b></a>', $this->compiler->compile($jade));
    }
}