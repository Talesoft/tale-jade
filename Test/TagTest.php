<?php

namespace Tale\Jade\Test;

use Tale\Jade\Compiler;

class TagTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Compiler */
    private $_compiler;

    public function setUp()
    {

        $this->_compiler = new Compiler([
            'pretty' => false,
            'handleErrors' => false
        ]);
    }

    public function testBasicTag()
    {

        $this->assertEquals('<p>Test</p>', $this->_compiler->compile('p Test'));
    }
}