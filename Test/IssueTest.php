<?php

namespace Tale\Jade\Test;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;
use Tale\Jade\Parser;

class IssueTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Renderer */
    private $_renderer;

    public function setUp()
    {

        $this->_renderer = new Renderer([
            'adapterOptions' => [
                'path' => __DIR__.'/cache/issues'
            ],
            'pretty' => false,
            'paths' => [__DIR__.'/views/issues']
        ]);
    }

    public function testIssue19()
    {

        $this->assertEquals('<h3>Columnus</h3><br><br><h3>Coluumns</h3><br><br><h3>Columns</h3>array(0=>array(\'class\'=>\'yii\\\\grid\\\\SerialColumn\',),1=>\'id\',2=>\'username\',3=>\'auth_key\',4=>\'password_hash\',5=>array(\'class\'=>\'yii\\\\grid\\\\ActionColumn\',),)array(0=>array(\'class\'=>\'yii\\\\grid\\\\SerialColumn\',),1=>\'id\',2=>\'username\',3=>\'auth_key\',4=>\'password_hash\',5=>\'password_reset_token\',6=>array(\'class\'=>\'yii\\\\grid\\\\ActionColumn\',),)', $this->_renderer->render('issue-19'));
    }

    public function testIssue33()
    {

        $this->assertEquals('<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0"><meta http-equiv="X-UA-Compatible" content="chrome=1"><link rel="shortcut icon" href="/favicon.ico"><link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Open+Sans:300,400,600"><link rel="stylesheet" href="/style.css">', $this->_renderer->render(
            'issue-33'
        ));
    }

    public function testIssue42()
    {

        $this->assertEquals('<a><b><c><d>Some TextSome further text</d></c><e></e></b><f></f></a><g></g><a><b><c><d><e><f><style>Some Text Some further text</style></f></e><e></e></d></c><f></f></b></a><g></g><a><b><c><d><e><f>Some TextSome further text</f></e><e></e></d></c></b><f></f></a><g></g>', $this->_renderer->render(
            'issue-42'
        ));
    }

    public function testIssue48()
    {

        $this->assertEquals('<h2>Hello</h2>', $this->_renderer->render(
            'issue-48/1'
        ));

        $this->assertEquals(' <button>Submit</button>', $this->_renderer->render(
            'issue-48/views/view.ctp'
        ));

        $this->assertEquals('<div id="clip_1"></div>', $this->_renderer->render(
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
            'adapterOptions' => [
                'path' => __DIR__.'/cache/issues'
            ],
            'lexerOptions' => [
                'indentWidth' => 2,
                'indentStyle' => ' '
            ],
            'pretty' => false,
            'paths' => [__DIR__.'/views/issues']
        ]);

        $this->assertEquals('<?php /** * CakePHP(tm) : Rapid Development Framework (http://cakephp.org) * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org) */?><!DOCTYPE html>', $renderer->compile($jade));
        $this->assertEquals('<?php /** * CakePHP(tm) : Rapid Development Framework (http://cakephp.org) * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org) */?><!DOCTYPE html>', $renderer->compileFile('issue-44'));
    }
}