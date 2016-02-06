<?php

namespace Tale\Test\Jade\Lexer;

use Tale\Jade\Lexer\Reader;

class ReaderTest extends \PHPUnit_Framework_TestCase
{

    public function testReadString()
    {

        $this->assertEmpty(
            (new Reader('some random string'))->readString(),
            'no quotes is not a string'
        );
        $this->assertEquals(
            'some random string', (new Reader('"some random string"'))->readString(),
            'double quoted string'
        );
        $this->assertEquals(
            'some random string', (new Reader("'some random string'"))->readString(),
            'single quoted string'
        );
        $this->assertEquals(
            'some "random string', (new Reader('"some \"random string"'))->readString(),
            'double quote inside double quoted string'
        );
        $this->assertEquals(
            'some \'random string', (new Reader("'some \\'random string'"))->readString(),
            'single quote inside single quoted string'
        );
    }

    /**
     * @dataProvider expressionProvider
     */
    public function testReadExpression($expected, $input, array $breakChars)
    {

        $this->assertEquals($expected, (new Reader($input))->readExpression($breakChars));
    }

    public function expressionProvider()
    {

        return [
            ['"some \" string" ? (some[stuff]) : \'\'', '"some \" string" ? (some[stuff]) : \'\', some other stuff', [',']]
        ];
    }

    /**
     * @dataProvider invalidBracketExpressionProvider
     */
    public function testInvalidBracketPlacement($input)
    {

        $this->setExpectedException(Reader\Exception::class);

        $reader = new Reader($input);
        $reader->readExpression();
    }

    public function invalidBracketExpressionProvider()
    {

        return [
            ['some ('],
            ['some {'],
            ['some ['],
            ['some )'],
            ['some ]'],
            ['some }'],
            ['some (a[b)c'],
            ['some (a[b)c]d'],
            ['some (a[b(c)d)e']
        ];
    }

    /**
     * @dataProvider readWhileInputProvider
     */
    public function testReadWhile($expected, $input, $callback)
    {

        $reader = new Reader($input);
        $this->assertEquals($expected, $reader->readWhile($callback));
    }

    public function readWhileInputProvider()
    {

        return [
            ['', '-fefwefwg', 'ctype_alpha'],
            ['', 'a56116313', 'ctype_digit'],
            ['abcdefgh', 'abcdefgh!fe', 'ctype_alpha'],
            ['618912351', '618912351a65', 'ctype_digit']
        ];
    }

    /**
     * @dataProvider readUntilInputProvider
     */
    public function testReadUntil($expected, $input, $callback)
    {

        $reader = new Reader($input);
        $this->assertEquals($expected, $reader->readUntil($callback));
    }

    public function readUntilInputProvider()
    {

        return [
            ['f', 'f611616', 'ctype_digit'],
            ['5', '5wefwfwef', 'ctype_alpha'],
            ['abcdefgh', 'abcdefgh6fe', 'ctype_digit'],
            ['618912351', '618912351a65', 'ctype_alpha']
        ];
    }

    public function testReadUntilNewLine()
    {

        $reader = new Reader("ajnwe pwemfo qwpmpowqf\nfqpfmo qwfonqwof");
        $this->assertEquals('ajnwe pwemfo qwpmpowqf', $reader->readUntilNewLine());
    }
}