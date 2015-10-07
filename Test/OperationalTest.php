<?php

namespace Tale\Jade\Test;

use Tale\Jade\Compiler;
use Exception;
use Tale\Jade\Lexer;
use Tale\Jade\Parser;
use Tale\Jade\Renderer;

class OperationalTest extends \PHPUnit_Framework_TestCase
{

    public function testLexerInstanciation()
    {

        try {

            new Lexer();
            $this->assertTrue(true);

        } catch(Exception $e) {

            $this->assertTrue(false, $e->getMessage());
        }
    }

    public function testCompilerInstanciation()
    {

        try {

            new Compiler();
            $this->assertTrue(true);

        } catch(Exception $e) {

            $this->assertTrue(false, $e->getMessage());
        }
    }

    public function testParserInstanciation()
    {

        try {

            new Parser();
            $this->assertTrue(true);

        } catch(Exception $e) {

            $this->assertTrue(false, $e->getMessage());
        }
    }

    public function testRendererInstanciation()
    {

        try {

            new Renderer();
            $this->assertTrue(true);

        } catch(Exception $e) {

            $this->assertTrue(false, $e->getMessage());
        }
    }
}