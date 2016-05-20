<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;
use Tale\Jade\Parser;

class IssueTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Renderer */
    private $renderer;

    public function setUp()
    {

        $this->renderer = new Renderer([
            'adapter_options' => [
                'path' => __DIR__.'/cache/issues'
            ],
            'pretty' => false,
            'paths' => [__DIR__.'/views/issues']
        ]);
    }

    public function testIssue19()
    {

        $this->assertEquals('<h3>Columnus</h3><br><br><h3>Coluumns</h3><br><br><h3>Columns</h3>array(0=>array(\'class\'=>\'yii\\\\grid\\\\SerialColumn\',),1=>\'id\',2=>\'username\',3=>\'auth_key\',4=>\'password_hash\',5=>array(\'class\'=>\'yii\\\\grid\\\\ActionColumn\',),)array(0=>array(\'class\'=>\'yii\\\\grid\\\\SerialColumn\',),1=>\'id\',2=>\'username\',3=>\'auth_key\',4=>\'password_hash\',5=>\'password_reset_token\',6=>array(\'class\'=>\'yii\\\\grid\\\\ActionColumn\',),)', $this->renderer->render('issue-19'));
    }

    public function testIssue33()
    {

        $this->assertEquals('<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0"><meta http-equiv="X-UA-Compatible" content="chrome=1"><link rel="shortcut icon" href="/favicon.ico"><link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Open+Sans:300,400,600"><link rel="stylesheet" href="/style.css">', $this->renderer->render(
            'issue-33'
        ));
    }

    public function testIssue42()
    {

        $this->assertEquals('<a><b><c><d>Some TextSome further text</d></c><e></e></b><f></f></a><g></g><a><b><c><d><e><f><style>Some Text Some further text</style></f></e><e></e></d></c><f></f></b></a><g></g><a><b><c><d><e><f>Some TextSome further text</f></e><e></e></d></c></b><f></f></a><g></g>', $this->renderer->render(
            'issue-42'
        ));
    }

    public function testIssue48()
    {

        $this->assertEquals('<h2>Hello</h2>', $this->renderer->render(
            'issue-48/1'
        ));

        $this->assertEquals('<button>Submit</button>', $this->renderer->render(
            'issue-48/views/view.ctp'
        ));

        $this->assertEquals('<div id="clip_1"></div>', $this->renderer->render(
            'issue-48/escaping',
            ['clipId' => 1]
        ));
    }

    public function testIssue44()
    {

        $jade = <<<JADE
-
\t\t/**
\t\t* CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
\t\t* Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
\t\t*/

doctype html
JADE;


        $renderer = new Renderer([
            'adapter_options' => [
                'path' => __DIR__.'/cache/issues'
            ],
            'pretty' => false,
            'paths' => [__DIR__.'/views/issues']
        ]);

        $this->assertEquals('<?php /*** CakePHP(tm) : Rapid Development Framework (http://cakephp.org)* Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)*/?><!DOCTYPE html>', $renderer->compile($jade));
        $this->assertEquals('<?php /*** CakePHP(tm) : Rapid Development Framework (http://cakephp.org)* Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)*/?><!DOCTYPE html><!DOCTYPE html><html><head><?=$view->Html->charset()?><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">', $renderer->compileFile('issue-44'));

        $this->assertEquals('<ul class="right hide-on-med-and-down"><li><?=$view->Html->link(__(\'Dashboard\'), [\'controller\' => \'Users\', \'action\' => \'index\'])?></li><li><?=$view->Html->link(__(\'Log Out\'), [\'controller\' => \'Users\', \'action\' => \'logout\'])?></li></ul><ul id="nav-mobile" class="side-nav"><li><?=$view->Html->link(__(\'Dashboard\'), [\'controller\' => \'Users\', \'action\' => \'index\'])?></li><li><?=$view->Html->link(__(\'Log Out\'), [\'controller\' => \'Users\', \'action\' => \'logout\'])?></li></ul><a class="button-collapse" href="#" data-activates="nav-mobile"><i class="material-icons">menu</i></a><div class="progress -main"><div class="indeterminate"></div></div>', $this->renderer->compileFile('issue-44/for_members.ctp.1'));
        $this->assertEquals('<ul class="right hide-on-med-and-down"><li><?=$view->Html->link(__(\'Dashboard\'), [\'controller\' => \'Users\', \'action\' => \'index\'])?></li><li><?=$view->Html->link(__(\'Log Out\'), [\'controller\' => \'Users\', \'action\' => \'logout\'])?></li></ul><ul id="nav-mobile" class="side-nav"><li><?=$view->Html->link(__(\'Dashboard\'), [\'controller\' => \'Users\', \'action\' => \'index\'])?></li><li><?=$view->Html->link(__(\'Log Out\'), [\'controller\' => \'Users\', \'action\' => \'logout\'])?></li></ul><a class="button-collapse" href="#" data-activates="nav-mobile"><i class="material-icons">menu</i></a><div class="progress -main"><div class="indeterminate"></div></div>', $this->renderer->compileFile('issue-44/for_members.ctp.2'));


        $renderer = new Renderer([
            'adapter_options' => [
                'path' => __DIR__.'/cache/issues'
            ],
            'lexer_options' => [
                'indent_width' => 2,
                'indent_style' => ' '
            ],
            'pretty' => false,
            'paths' => [__DIR__.'/views/issues']
        ]);

        $this->assertEquals('<?php /*** CakePHP(tm) : Rapid Development Framework (http://cakephp.org)* Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)*/?><!DOCTYPE html>', $renderer->compile($jade));
        $this->assertEquals('<?php /*** CakePHP(tm) : Rapid Development Framework (http://cakephp.org)* Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)*/?><!DOCTYPE html><!DOCTYPE html><html><head><?=$view->Html->charset()?><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">', $renderer->compileFile('issue-44'));

        $this->assertEquals('<ul class="right hide-on-med-and-down"><li><?=$view->Html->link(__(\'Dashboard\'), [\'controller\' => \'Users\', \'action\' => \'index\'])?></li><li><?=$view->Html->link(__(\'Log Out\'), [\'controller\' => \'Users\', \'action\' => \'logout\'])?></li></ul><ul id="nav-mobile" class="side-nav"><li><?=$view->Html->link(__(\'Dashboard\'), [\'controller\' => \'Users\', \'action\' => \'index\'])?></li><li><?=$view->Html->link(__(\'Log Out\'), [\'controller\' => \'Users\', \'action\' => \'logout\'])?></li></ul><a class="button-collapse" href="#" data-activates="nav-mobile"><i class="material-icons">menu</i></a><div class="progress -main"><div class="indeterminate"></div></div>', $this->renderer->compileFile('issue-44/for_members.ctp.1'));
        $this->assertEquals('<ul class="right hide-on-med-and-down"><li><?=$view->Html->link(__(\'Dashboard\'), [\'controller\' => \'Users\', \'action\' => \'index\'])?></li><li><?=$view->Html->link(__(\'Log Out\'), [\'controller\' => \'Users\', \'action\' => \'logout\'])?></li></ul><ul id="nav-mobile" class="side-nav"><li><?=$view->Html->link(__(\'Dashboard\'), [\'controller\' => \'Users\', \'action\' => \'index\'])?></li><li><?=$view->Html->link(__(\'Log Out\'), [\'controller\' => \'Users\', \'action\' => \'logout\'])?></li></ul><a class="button-collapse" href="#" data-activates="nav-mobile"><i class="material-icons">menu</i></a><div class="progress -main"><div class="indeterminate"></div></div>', $this->renderer->compileFile('issue-44/for_members.ctp.2'));
    }

    public function testIssue55()
    {

        $this->assertEquals('<div class="col s6 right-align"><strong>Sign In</strong> / <?=$view->Html->link(\'Sign Up\', [\'action\' => \'add\'])?></div>', $this->renderer->compile('.col.s6.right-align #[strong Sign In] / !{$view->Html->link(\'Sign Up\', [\'action\' => \'add\'])}'));
    }

    public function testIssue57()
    {

        $this->assertEquals('<pre><code><?=htmlentities(\'<?php\', \ENT_QUOTES, \'UTF-8\')?><?=htmlentities(\'$foo = \'hey\';\', \ENT_QUOTES, \'UTF-8\')?></code></pre>', $this->renderer->compile("pre: code!.\n\t<?php\n\t\$foo = 'hey';"));
    }

    public function testIssue66()
    {

        $this->assertEquals('<div class="blogentry" itemscope itemtype="http://schema.org/BlogPosting"></div>', $this->renderer->compile('.blogentry(itemscope itemtype="http://schema.org/BlogPosting")'));
    }

    public function testIssue95()
    {

        $this->assertEquals('<blockquote></blockquote>', $this->renderer->compile('blockquote'));
    }

    public function testIssue88()
    {

        $this->assertEquals('<some-tag></some-tag><some-other-tag></some-other-tag>', $this->renderer->compile("some-tag \nsome-other-tag"));
        $this->assertEquals('<some-tag></some-tag><some-other-tag></some-other-tag>', $this->renderer->compile("some-tag\t\nsome-other-tag"));
        $this->assertEquals('<some-tag></some-tag><some-other-tag></some-other-tag>', $this->renderer->compile("some-tag  \nsome-other-tag"));
        $this->assertEquals('<some-tag></some-tag><some-other-tag></some-other-tag>', $this->renderer->compile("some-tag \t \nsome-other-tag"));

        $this->assertEquals('<i class="fa fa-fw fa-gear">Some text</i>', $this->renderer->compile('i.fa.fa-fw.fa-gear Some text'));
        $this->assertEquals('<i class="fa fa-fw fa-gear"> Some text</i>', $this->renderer->compile('i.fa.fa-fw.fa-gear  Some text'));
        $this->assertEquals('<i class="fa fa-fw fa-gear">Some text</i>', $this->renderer->compile("i.fa.fa-fw.fa-gear\n\t| Some text"));
        $this->assertEquals('<i class="fa fa-fw fa-gear"> Some text</i>', $this->renderer->compile("i.fa.fa-fw.fa-gear\n\t|  Some text"));
    }
}