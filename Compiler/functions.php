<?php

namespace Tale\Jade\Compiler;

/**
 * Checks if a variables is scalar (or "not an expression").
 *
 * These values don't get much special handling, they are mostly
 * simple attributes values like `type="button"` or `method='post'`
 *
 * A scalar value is either a closed string containing only
 * a-z, A-Z, 0-9, _ and -, e.g. Some-Static_Value
 * or a quote-enclosed string that can contain anything
 * except the quote style it used
 * e.g. "Some Random String", 'This can" contain quotes"'
 *
 * @param string $value the value to be checked
 *
 * @return bool
 */
function is_scalar($value)
{

    return preg_match('/^(["\'])[^\1]*\1$/i', $value) === 1 ? true : false;
}

/**
 * Checks if a value is a variables.
 *
 * A variables needs to start with $.
 * After that only a-z, A-Z and _ can follow
 * After that you can use any character of
 * a-z, A-Z, 0-9, _, [, ], -, >, ' and "
 * This will match all of the following:
 *
 * $__someVar
 * $obj->someProperty
 * $arr['someKey']
 * $arr[0]
 * $obj->someArray['someKey']
 * etc.
 *
 * @param string $value the value to be checked
 *
 * @return bool
 */
function is_variable($value)
{

    return preg_match(
        '/^\$[a-z_\$](\$?\w*|\[[^\]]+\]|\->(\$?\w+|\{[^\}]+\}))*$/i',
        $value
    ) === 1 ? true : false;
}