<?php

namespace Tale\Jade;

use Tale\Reader;
use Tale\ReaderException;

class Util
{

    const CHECK_FORMAT = 'isset(%s) ? %s : %s';

    private function __construct() {}

    public static function isStringValue($value, $encoding = null)
    {
        if ((!is_string($value) && !is_numeric($value)) || $value === '')
            return false;

        $reader = new Reader($value, $encoding);
        $string = $reader->readString(null, true);
        return $string !== null && $string === $value;
    }

    public static function isScalarValue($value, $encoding = null)
    {

        return self::isStringValue($value, $encoding) || is_numeric($value);
    }

    public static function isVariableValue($value)
    {

        return preg_match(
            '/^\$[a-z_\$](\$?\w*|\[[^\]]+\]|\->(\$?\w+|\{[^\}]+\})|\{[^\}]+\})*$/i',
            $value
        ) ? true : false;
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

            if (!$reader->hasLength())
                break;

            $newValue .= $reader->peek();
            $reader->consume();
        }

        return $newValue;
    }

    public static function check($value, $defaultValue = null)
    {

        if (!self::isVariableValue($value))
            return $value;

        return sprintf(
            self::CHECK_FORMAT,
            $value,
            $value,
            self::exportValue($defaultValue)
        );
    }

    public static function exportValue($value, $encoding = null, $quoteStyle = null, $check = true)
    {

        $quoteStyle = $quoteStyle ?: '\'';

        if (is_array($value)) {

            $pairs = [];
            foreach ($value as $key => $val) {

                $key = self::exportValue($key, $encoding, $quoteStyle);
                $val = self::exportValue($val, $encoding, $quoteStyle);
                $pairs[] = "$key=>$val";
            }

            return '['.implode(',', $pairs).']';
        }

        //Trigger default exporting for objects (__set_state stuff)
        if (is_object($value))
            return var_export($value, true);

        //Trigger the default "Resource (#X)" thing
        if (is_resource($value))
            return (string)$value;

        //We could actually ignore these, but let's at least
        //make them consistent.
        if (strtolower($value) === 'null' || $value === null)
            return 'null';
        else if (strtolower($value) === 'false' || $value === false)
            return 'false';
        else if (strtolower($value) === 'true' || $value === true)
            return 'true';

        if (self::isStringValue($value)) {

            $reader = new Reader($value, $encoding);
            $string = $reader->readString();
            return $quoteStyle.str_replace($quoteStyle, "\\$quoteStyle", $string).$quoteStyle;
        }

        //Numeric values get passed through
        if (is_numeric($value))
            return $value;

        //Assume a PHP expression on anything else.
        return $check ? self::check($value) : $value;
    }
}