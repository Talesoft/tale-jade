<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;

class ExpressionAndCodeTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Compiler */
    private $compiler;

    public function setUp()
    {

        $this->compiler = new Compiler();
    }

    public function testSimpleExpression()
    {

        $this->assertEquals('<?=htmlentities(isset($someExpression) ? $someExpression : \'\', \ENT_QUOTES, \'UTF-8\')?>', $this->compiler->compile('= $someExpression'));
    }

    public function testUnescapedExpression()
    {

        $this->assertEquals('<?=isset($someExpression) ? $someExpression : \'\'?>', $this->compiler->compile('!= $someExpression'));
    }

    public function testFunctionExpression()
    {

        $this->assertEquals('<?=htmlentities(someFunctionCall(), \ENT_QUOTES, \'UTF-8\')?>', $this->compiler->compile('= someFunctionCall()'));
    }

    public function testUnescapedFunctionExpression()
    {

        $this->assertEquals('<?=someFunctionCall()?>', $this->compiler->compile('!= someFunctionCall()'));
    }

    public function testSimpleCode()
    {

        $this->assertEquals('<?php while($i < 15) doSomething();?>', $this->compiler->compile('- while($i < 15) doSomething();'));
    }

    public function testCodeBlock()
    {

        $this->assertEquals('<?php foreach ($post in $posts) {doSomethingWith($post);}$array = ["a", "b", "c"];?>', $this->compiler->compile('-
    foreach ($post in $posts) {
        doSomethingWith($post);
    }

    $array = ["a", "b", "c"];'));
    }

    public function testIssue21()
    {

        $this->assertEquals(
            '<?=$view->render(\'_search\', [\'model\' => $searchModel])?>',
            $this->compiler->compile('!=$view->render(\'_search\', [\'model\' => $searchModel])')
        );
    }
}