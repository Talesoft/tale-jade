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
 * read it here https://github.com/Talesoft/tale-jade/blob/master/LICENSE.md
 *
 * @category   Presentation
 * @package    Tale\Jade\Compiler
 * @author     Torben Koehn <torben@talesoft.codes>
 * @author     Talesoft <info@talesoft.codes>
 * @copyright  Copyright (c) 2015-2016 Torben Köhn (http://talesoft.codes)
 * @license    https://github.com/Talesoft/tale-jade/blob/master/LICENSE.md MIT License
 * @version    1.4.5
 * @link       http://jade.talesoft.codes/docs/files/Compiler.Exception.html
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
 * @author     Torben Koehn <torben@talesoft.codes>
 * @author     Talesoft <info@talesoft.codes>
 * @copyright  Copyright (c) 2015-2016 Torben Köhn (http://talesoft.codes)
 * @license    https://github.com/Talesoft/tale-jade/blob/master/LICENSE.md MIT License
 * @version    1.4.5
 * @link       http://jade.talesoft.codes/docs/classes/Tale.Jade.Compiler.Exception.html
 * @since      File available since Release 1.0
 */
class Exception extends \Exception
{
}