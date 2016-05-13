<?php
/**
 * The Tale Jade Compiler Exception.
 *
 * Contains an exception that is thrown when the compiler doesnt find files
 * or encounters invalid node relations
 *
 * LICENSE:
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * @category   Presentation
 * @package    Tale\Jade\Compiler
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/files/Compiler.Exception.html
 * @since      File available since Release 1.0
 */

namespace Tale\Jade\Compiler;

/**
 * Represents an exception that is thrown during the compilation process.
 *
 * This exception is thrown when the compiler doesnt find
 * a file or encounters invalid node relations
 *
 * @category   Presentation
 * @package    Tale\Jade\Compiler
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/classes/Tale.Jade.Compiler.Exception.html
 * @since      File available since Release 1.0
 */
class Exception extends \Exception
{
}