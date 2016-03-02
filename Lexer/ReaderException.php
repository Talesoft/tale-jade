<?php
/**
 * The Tale Jade Lexer Reader Exception.
 *
 * Contains an exception that is thrown when the reader fails to read
 * a string correctly
 *
 * LICENSE:
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * @category   Presentation
 * @package    Tale\Jade\Lexer\Reader
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Talesoft (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.0
 * @link       http://jade.talesoft.io/docs/files/Compiler.Exception.html
 * @since      File available since Release 1.4.1
 */

namespace Tale\Jade\Lexer;

/**
 * Represents an exception that is thrown during the reading process.
 *
 * This exception is thrown when the reader fails to read
 * a string correctly
 *
 * @category   Presentation
 * @package    Tale\Jade\Lexer\Reader
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Talesoft (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.0
 * @link       http://jade.talesoft.io/docs/classes/Tale.Jade.Lexer.Reader.Exception.html
 * @since      File available since Release 1.0
 */
class ReaderException extends \Exception
{
}