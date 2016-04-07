<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;

class IdTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Compiler */
    private $compiler;

    public function setUp()
    {

        $this->compiler = new Compiler();
    }

    public function testId()
    {

        $this->assertEquals('<div id="test">Test</div>', $this->compiler->compile('#test Test'));
    }

    public function testNestedId()
    {

        $jade = <<<JADE
#testParent
    #testChild Test
JADE;

        $this->assertEquals('<div id="testParent"><div id="testChild">Test</div></div>', $this->compiler->compile($jade));
    }

    public function testTagIdCombination()
    {

        $this->assertEquals('<p id="test">Test</p>', $this->compiler->compile('p#test Test'));
    }

    public function testNestedTagIdCombination()
    {

        $jade = <<<JADE
div#testParent
    p#testChild Test
JADE;

        $this->assertEquals('<div id="testParent"><p id="testChild">Test</p></div>', $this->compiler->compile($jade));
    }

    public function testComplexTagIdCombination()
    {

        $jade = <<<JADE
#testContainer
    nav#testNav1
        ul#testMenu1
            li#testItem1
                a#testLink1
            li#testItem2
                a#testLink2
            li#testItem3
                a#testLink3
    nav#testNav2
        ul#testMenu2
            li#testItem4
                a#testLink4
            li#testItem5
                a#testLink5
            li#testItem6
                a#testLink6
    nav#testNav3
        ul#testMenu3
            li#testItem7
                a#testLink7
            li#testItem8
                a#testLink8
            li#testItem9
                a#testLink9
JADE;

        $this->assertEquals('<div id="testContainer"><nav id="testNav1"><ul id="testMenu1"><li id="testItem1"><a id="testLink1"></a></li><li id="testItem2"><a id="testLink2"></a></li><li id="testItem3"><a id="testLink3"></a></li></ul></nav><nav id="testNav2"><ul id="testMenu2"><li id="testItem4"><a id="testLink4"></a></li><li id="testItem5"><a id="testLink5"></a></li><li id="testItem6"><a id="testLink6"></a></li></ul></nav><nav id="testNav3"><ul id="testMenu3"><li id="testItem7"><a id="testLink7"></a></li><li id="testItem8"><a id="testLink8"></a></li><li id="testItem9"><a id="testLink9"></a></li></ul></nav></div>', $this->compiler->compile($jade));
    }
}