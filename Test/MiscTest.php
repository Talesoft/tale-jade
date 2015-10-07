<?php

namespace Tale\Jade\Test;

use Tale\Jade\Compiler;
use Tale\Jade\Lexer;

class MiscTest extends \PHPUnit_Framework_TestCase
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

    public function testNewLineEscaping()
    {
        $this->assertEquals("<input value=\"Line 1\nLine 2\">", $this->_compiler->compile('input(value="Line 1\nLine 2")'));
    }

    public function testTabEscaping()
    {
        $this->assertEquals("<input value=\"Line 1\tLine 2\">", $this->_compiler->compile('input(value="Line 1\tLine 2")'));
    }

    public function testCarriageReturnEscaping()
    {
        $this->assertEquals("<input value=\"Line 1\rLine 2\">", $this->_compiler->compile('input(value="Line 1\rLine 2")'));
    }
}