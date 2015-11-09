<?php

namespace Tale\Jade\Test;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;

class VariableTest extends \PHPUnit_Framework_TestCase
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
                'paths' => [__DIR__.'/views/variables']
            ]
        ]);
    }

    public function testAssignment()
    {

        $this->assertEquals('<p>Hello World!</p>nowrap212<div style="width: 100%; height: 50%"></div>', $this->_renderer->render('assignment', [
            'existing' => ['style' => ['width' => '100%'], 'class' => 'test']
        ]));
    }

    public function testIssue19()
    {

        $this->assertEquals('<h3>Columnus</h3><br><br><h3>Coluumns</h3><br><br><h3>Columns</h3>array(0=>array(\'class\'=>\'yii\\\\grid\\\\SerialColumn\',),1=>\'id\',2=>\'username\',3=>\'auth_key\',4=>\'password_hash\',5=>array(\'class\'=>\'yii\\\\grid\\\\ActionColumn\',),)array(0=>array(\'class\'=>\'yii\\\\grid\\\\SerialColumn\',),1=>\'id\',2=>\'username\',3=>\'auth_key\',4=>\'password_hash\',5=>\'password_reset_token\',6=>array(\'class\'=>\'yii\\\\grid\\\\ActionColumn\',),)', $this->_renderer->render('issue-19'));
    }
}