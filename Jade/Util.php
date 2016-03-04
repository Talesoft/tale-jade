<?php

namespace Tale\Jade;

use Tale\Reader;
use Tale\ReaderException;

class Util
{

    private static function __construct() {}

    public static function isStringValue($value, $encoding = null)
    {
        if ((!is_string($value) && !is_numeric($value)) || $value === '')
            return false;

        $reader = new Reader($value, $encoding);

        $string = $reader->readString(null, true);
        return $string !== null && $string === $value;
    }

    public static function isNumericValue($value)
    {

        if ($value === '')
            return false;

        return preg_match('/^([0-9]+)?(\.[0-9]+)?$/', $value) === 1;
    }

    public static function isScalarValue($value, $encoding = null)
    {

        return self::isStringValue($value, $encoding) || self::isNumericValue($value);
    }

    public static function isVariableValue($value)
    {

        return preg_match('/^\$[a-z_\$](\$?\w*|\[[^\]]+\]|\->(\$?\w+|\{[^\}]+\}))*$/i', $value) ? true : false;
    }

    public static function interpolate(
        $value,
        callable $replacer,
        $prefixes = null,
        array $brackets = null,
        $encoding = null
    )
    {

        $prefixes = $prefixes ?: '!#';
        $brackets = $brackets ?: ['[' => ']', '{' => '}'];

        $parse = false;
        foreach (str_split($prefixes) as $prefix)
            if (mb_strstr($value, $prefix))
                $parse = true;

        if (!$parse)
            return $value;

        $reader = new Reader($value, $encoding);
        $newValue = '';
        while ($reader->hasLength()) {

            if ($reader->peekChars($prefixes) && in_array($reader->peek(1, 1), array_keys($brackets))) {

                $prefix = $reader->peek();
                $reader->consume();
                $openBracket = $reader->peek();
                $closeBracket = $brackets[$openBracket];
                $reader->consume();

                $subject = $reader->readExpression([$closeBracket]);

                if (!$reader->peekChar($closeBracket))
                    throw new ReaderException(
                        "Failed to read interpolation: Interpolation was not closed with a $closeBracket"
                    );

                $reader->consume();
                $newValue .= $replacer($subject, $prefix, $openBracket, $closeBracket);
            }

            $newValue .= $reader->peek();
            $reader->consume();
        }

        return $newValue;
    }
}