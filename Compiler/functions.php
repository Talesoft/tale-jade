<?php

namespace Tale\Jade\Compiler {
    //We use the bracket-style here to be able to compile
    //this file into compiled jade templates for valid PHP

    if (!function_exists(__NAMESPACE__.'\\build_value')) {

        /**
         * Builds an attribute or argument value.
         *
         * Objects get converted to arrays
         * Arrays will be imploded by '' (values are concatenated)
         *
         * ['a', 'b', ['c', ['d']]]
         * will become
         * 'abcd'
         *
         * The result will be enclosed by the quotes passed to $quoteStyle
         *
         * @param mixed  $value      The value to build
         * @param string $quoteStyle The quoting style to use
         * @param bool   $escaped    Escape the value or not
         *
         * @return string The built value
         */
        function build_value($value, $quoteStyle, $escaped)
        {

            if (is_object($value))
                $value = (array)$value;

            return $quoteStyle.($escaped ? htmlentities(is_array($value) ? flatten($value, '') : $value, \ENT_QUOTES) : ((string)$value)).$quoteStyle;
        }
    }

    if (!function_exists(__NAMESPACE__.'\\build_data_value')) {

        /**
         * Builds a data-attribute value.
         *
         * If it's an object or an array, it gets converted to JSON automatically
         * If not, the value stays scalar
         *
         * JSON will automatically be enclosed by ', other results will use
         * $quoteStyle respectively
         *
         * 'a'
         * will become
         * 'a'
         *
         * ['a', 'b']
         * will become
         * '["a", "b"]' (JSON)
         *
         * @param mixed  $value      The value to build
         * @param string $quoteStyle The quoting style to use
         * @param bool   $escaped    Escape the value or not
         *
         * @return string The built value
         */
        function build_data_value($value, $quoteStyle, $escaped)
        {

            if (is_object_or_array($value))
                return '\''.json_encode($value).'\'';

            return $quoteStyle.($escaped ? htmlentities($value, \ENT_QUOTES) : ((string)$value)).$quoteStyle;
        }
    }

    if (!function_exists(__NAMESPACE__.'\\build_style_value')) {

        /**
         * Builds a style-attribute string from a value.
         *
         * ['color' => 'red', 'width: 100%', ['height' => '20px']]
         * will become
         * 'color: red; width: 100%; height: 20px;'
         *
         * @param mixed  $value      The value to build
         * @param string $quoteStyle The quoting style to use
         *
         * @return string The built value
         */
        function build_style_value($value, $quoteStyle)
        {

            if (is_object($value))
                $value = (array)$value;

            if (is_array($value))
                $value = flatten($value, '; ', ': ');

            return $quoteStyle.((string)$value).$quoteStyle;
        }
    }

    if (!function_exists(__NAMESPACE__.'\\build_class_value')) {

        /**
         * Builds a class-attribute string from a value.
         *
         *['a', 'b', ['c', ['d', 'e']]]
         * will become
         * 'a b c d e'
         *
         * @param mixed  $value      The value to build
         * @param string $quoteStyle The quoting style to use
         *
         * @return string The built value
         */
        function build_class_value($value, $quoteStyle)
        {

            if (is_object($value))
                $value = (array)$value;

            if (is_array($value))
                $value = flatten($value);

            return $quoteStyle.((string)$value).$quoteStyle;
        }
    }

    if (!function_exists(__NAMESPACE__.'\\is_null_or_false')) {

        /**
         * Checks if a value is _exactly_ either null or false.
         *
         * @param mixed $value The value to check
         *
         * @return bool
         */
        function is_null_or_false($value)
        {

            return $value === null || $value === false;
        }
    }

    if (!function_exists(__NAMESPACE__.'\\is_array_null_or_false')) {

        /**
         * Checks if a whole array is _exactly_ null or false.
         *
         * Not the array itself, but all values in the array
         *
         * @param array $value The array to check
         *
         * @return bool
         */
        function is_array_null_or_false(array $value)
        {

            return count(array_filter($value, __NAMESPACE__.'\\is_null_or_false')) === count($value);
        }
    }

    if (!function_exists(__NAMESPACE__.'\\is_object_or_array')) {

        /**
         * Checks if a value is either an object or an array.
         *
         * Kind of like !isScalar && !isExpression
         *
         * @param mixed $value The value to check
         *
         * @return bool
         */
        function is_object_or_array($value)
        {

            return is_object($value) || is_array($value);
        }
    }

    if (!function_exists(__NAMESPACE__.'\\flatten')) {

        /**
         * Flattens an array and combines found values with $separator.
         *
         * If there are string-keys and an $argSeparator is set, it will
         * also implode those to to a single value
         *
         * With the default options
         * ['a', 'b' => 'c', ['d', 'e' => 'f', ['g' => 'h']]]
         * will become
         * 'a b=c d e=f g=h'
         *
         * @param array  $array        The array to flatten
         * @param string $separator    The separator to implode pairs with
         * @param string $argSeparator The separator to implode keys and values with
         *
         * @return string The compiled string
         */
        function flatten(array $array, $separator = ' ', $argSeparator = '=')
        {

            $items = [];
            foreach ($array as $key => $value) {

                if (is_object($value))
                    $value = (array)$value;

                if (is_array($value))
                    $value = flatten($value, $separator, $argSeparator);

                if (is_string($key))
                    $items[] = "$key$argSeparator$value";
                else
                    $items[] = $value;
            }

            return implode($separator, $items);
        }
    }
}