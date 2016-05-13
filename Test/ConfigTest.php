<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;

class ConfigTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider compilerOptionProvider
     */
    public function testCompilerGetsOptions($option, $value)
    {

        $compiler = new Compiler([$option => $value]);

        $this->assertEquals($value, $compiler->getOption($option));
    }

    /**
     * @dataProvider compilerOptionProvider
     */
    public function testRendererForwardsOptionsToCompiler($option, $value)
    {

        $renderer = new Renderer([$option => $value]);

        $this->assertEquals($value, $renderer->getCompiler()->getOption($option));
    }

    /**
     * @dataProvider lexerOptionProvider
     */
    public function testRendererForwardsOptionsToLexer($option, $value)
    {

        $renderer = new Renderer(['lexer_options' => [$option => $value]]);

        $this->assertEquals($value, $renderer->getCompiler()->getParser()->getLexer()->getOption($option));
    }

    public function compilerOptionProvider()
    {

        return [
            ['paths', ['/a', '/b', '/c']],
            ['pretty', true],
            ['indent_style', '-'],
            ['indent_width', 8],
            ['stand_alone', true],
            ['extensions', ['.a', '.b', '.c']],
            ['mode', Compiler::MODE_XHTML]
        ];
    }

    public function lexerOptionProvider()
    {

        return [
            ['indent_width', 8],
            ['indent_style', "\t"]
        ];
    }
}