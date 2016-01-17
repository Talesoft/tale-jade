<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;

class ExpressionAndCodeTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Compiler */
    private $_compiler;

    public function setUp()
    {

        $this->_compiler = new Compiler();
    }

    public function testSimpleExpression()
    {

        $this->assertEquals('<?=htmlentities(isset($someExpression) ? $someExpression : \'\', \ENT_QUOTES, \'UTF-8\')?>', $this->_compiler->compile('= $someExpression'));
    }

    public function testUnescapedExpression()
    {

        $this->assertEquals('<?=isset($someExpression) ? $someExpression : \'\'?>', $this->_compiler->compile('!= $someExpression'));
    }

    public function testFunctionExpression()
    {

        $this->assertEquals('<?=htmlentities(someFunctionCall(), \ENT_QUOTES, \'UTF-8\')?>', $this->_compiler->compile('= someFunctionCall()'));
    }

    public function testUnescapedFunctionExpression()
    {

        $this->assertEquals('<?=someFunctionCall()?>', $this->_compiler->compile('!= someFunctionCall()'));
    }

    public function testSimpleCode()
    {

        $this->assertEquals('<?php while($i < 15) doSomething();?>', $this->_compiler->compile('- while($i < 15) doSomething();'));
    }

    public function testCodeBlock()
    {

        $this->assertEquals('<?php foreach ($post in $posts) {doSomethingWith($post); } $array = ["a", "b", "c"];?>', $this->_compiler->compile('-
    foreach ($post in $posts) {
        doSomethingWith($post);
    }

    $array = ["a", "b", "c"];'));
    }

    public function testIssue21()
    {

        $this->assertEquals(
            '<?=$view->render(\'_search\', [\'model\' => $searchModel])?>',
            $this->_compiler->compile('!=$view->render(\'_search\', [\'model\' => $searchModel])')
        );
    }
}