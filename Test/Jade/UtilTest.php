<?php

namespace Tale\Test\Jade;

use Tale\Jade\Util;

class UtilTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider scalarValueProvider
     */
    public function testIsScalarValue($value)
    {

        $this->assertTrue(Util::isScalarValue($value), sprintf('`%s` value', $value));
    }

    /**
     * @dataProvider nonScalarValueProvider
     */
    public function testIsNotScalarValue($value)
    {

        $this->assertFalse(Util::isScalarValue($value), sprintf('`%s` value', $value));
    }

    /**
     * @dataProvider variableValueProvider
     */
    public function testIsVariableValue($value)
    {

        $this->assertTrue(Util::isVariableValue($value), sprintf('`%s` value', $value));
    }

    /**
     * @dataProvider nonVariableValueProvider
     */
    public function testIsNotVariableValue($value)
    {

        $this->assertFalse(Util::isVariableValue($value), sprintf('`%s` value', $value));
    }

    public function testInterpolation()
    {

        $this->assertEquals('Some interpolated text', Util::interpolate('Some #{$text}', function ($value, $escaped, $checked, $openBracket, $closeBracket) {

            if ($value === '$text' && $escaped && $checked && $openBracket === '{' && $closeBracket === '}')
                return 'interpolated text';

            return 'fail 1';
        }));

        $this->assertEquals('Some interpolated text', Util::interpolate('Some ![$text]', function ($value, $escaped, $checked, $openBracket, $closeBracket) {

            if ($value === '$text' && !$escaped && $checked && $openBracket === '[' && $closeBracket === ']')
                return 'interpolated text';

            return 'fail 2';
        }));

        $this->assertEquals('Some interpolated text', Util::interpolate('Some ![$text[someFunc()]]', function ($value, $escaped, $checked, $openBracket, $closeBracket) {

            if ($value === '$text[someFunc()]' && !$escaped && $checked && $openBracket === '[' && $closeBracket === ']')
                return 'interpolated text';

            return 'fail 3';
        }));

        $this->assertEquals('Some interpolated text', Util::interpolate('Some ?#{$text}', function ($value, $escaped, $checked, $openBracket, $closeBracket) {

            if ($value === '$text' && $escaped && !$checked && $openBracket === '{' && $closeBracket === '}')
                return 'interpolated text';

            return 'fail 4';
        }));

        $this->assertEquals('Some interpolated text', Util::interpolate('Some ?!{$text}', function ($value, $escaped, $checked, $openBracket, $closeBracket) {

            if ($value === '$text' && !$escaped && !$checked && $openBracket === '{' && $closeBracket === '}')
                return 'interpolated text';

            return 'fail 5';
        }));
    }

    /**
     * @dataProvider exportValueProvider
     */
    public function testExportValue($expected, $value)
    {

        $this->assertEquals($expected, Util::exportValue($value));
    }

    public function scalarValueProvider()
    {

        return [
            ['"abcdef"'],
            ['"abc\"def"'],
            ["'abcdef'"],
            ["'abc\\'def'"],
            ['01234'],
            ['1234'],
            ['12.34'],
            ['0.12'],
            ['.012'],
            ['.12']
        ];
    }

    public function nonScalarValueProvider()
    {

        return [
            ['"abc" ? "abc": "def"'],
            ['"abc"[0]'],
            ["some_function()"],
            ["SOME_CONST"],
            ['some_function(SOME_CONST)'],
            ['$someVar'],
            ['$someVar["abc"]'],
            ['%someprefixedstuff']
        ];
    }

    public function variableValueProvider()
    {

        return [
            ['$someVar'],
            ['$$someVar'],
            //['${$someVar}'],
            ['$someVar["a"]'],
            ['$someVar[0]'],
            ['$someVar[\'b\']'],
            ['$someVar->someProp'],
            ['$someVar->{$someProp}'],
            ['$someVar->someProp->someProp'],
            ['$someVar->someProp["a"]'],
            ['$someVar->someProp[0]'],
            ['$someVar->some{$prop}']
        ];
    }

    public function nonVariableValueProvider()
    {

        return [
            ['some_func()'],
            ['SOME_CONST'],
            ['$a ? $b : $c']
        ];
    }

    public function exportValueProvider()
    {

        return [
            ['\'some string\'', '"some string"'],
            ['\'some \\\' string\'', '"some \' string"'],
            ['\'some string\'', '\'some string\''],
            ['\'some \\\' string\'', '\'some \\\' string\''],
            ['12345', '12345'],
            ['0.1234', '0.1234'],
            ['.1234', '.1234'],
            ['null', NULL],
            ['null', 'NULL'],
            ['true', TRUE],
            ['true', 'TRUE'],
            ['false', FALSE],
            ['false', 'FALSE'],
            ['isset($var->prop) ? $var->prop : null', '$var->prop'],
            ['SOME_CONST', 'SOME_CONST'],
            ['some_func($a)', 'some_func($a)'],
            ['[0=>some_func($a)]', ['some_func($a)']],
            ['[\'some string\'=>isset($someVar) ? $someVar : null]', ['"some string"' => '$someVar']]
        ];
    }
}