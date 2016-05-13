<?php
/**
 * The Tale Jade Stream Renderer-Adapter Stream Wrapper
 *
 * Contains a Stream Wrapper to parse and read the special Data-URI generated
 * by the Stream-Adapter
 *
 * This file is part of the Tale Jade Template Engine for PHP
 *
 * LICENSE:
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * @category   Presentation
 * @package    Tale\Jade\Renderer\Adapter\Stream
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/files/Renderer.Adapter.Stream.Wrapper.html
 * @since      File available since Release 1.0
 */

namespace Tale\Jade\Renderer\Adapter\Stream;

use RuntimeException;

/**
 * Provides a Stream Wrapper that reads a special Data-URI.
 *
 * See the php docs on Stream Wrappers to get to know about this
 *
 * It allows you to create own URL wrappers and handle them by yourself
 * It works with most of the f*-functions of PHP
 * (fopen, fread, fwrite, INCLUDE(!!!!), etc.)
 *
 * @category   Presentation
 * @package    Tale\Jade\Renderer\Adapter\Stream
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/classes/Tale.Jade.Renderer.Adapter.Stream.Wrapper.html
 * @since      File available since Release 1.0
 */
class Wrapper
{

    /**
     * The input data (compiled PHTML).
     *
     * @var string
     */
    private $data;

    /**
     * The current position in our $_data.
     *
     * @var int
     */
    private $position;

    /**
     * The length of our $_data.
     *
     * @var int
     */
    private $length;

    /**
     * This gets called when a url-stream is opened with the wrapper-scheme.
     *
     * (e.g. fopen('tale-jade://data;...'), INCLUDE('tale-jade://data;...')
     *
     * @param string $uri          the Data-URI this stream was opened with
     * @param string $mode         the stream read/write-mode (useless here)
     * @param int    $options      the flags for this stream instance (useless here)
     * @param string &$opened_path the path that got actually opened (useless here)
     *
     * @return bool
     */
    public function stream_open($uri, $mode, $options, &$opened_path)
    {

        //Abstract mb_* functions to get UTF-8 working correctly in this bitch
        $substr = function_exists('mb_substr') ? 'mb_substr' : 'substr';
        $strlen = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
        $strpos = function_exists('mb_strpos') ? 'mb_strpos' : 'strpos';

        //Our data URI could look like this:
        // tale-jade://data;<base64-encoded-phtml>
        //We strip everything behind th first ;, the result would be only
        // <base64-encoded-phtml>
        //We decode that and $_data will contain only the pure, compiled PHTML
        //ready for inclusion
        $this->data = base64_decode($substr($uri, $strpos($uri, ';') + 1));

        $this->position = 0;
        $this->length = $strlen($this->data);

        return true;
    }

    /**
     * This gets called when anything tries to read from this.
     *
     * (opened) stream (e.g. fread, fgets, fgetcsv, INCLUDE(!!!) etc.)
     *
     * We return the fitting chunk of our PHTML and
     * add that length of that to our current position so that the next
     * call will read the next chunk, rinse and repeat
     *
     * @param int $length the length of the chunk to read
     *
     * @return string
     */
    public function stream_read($length)
    {

        //Abstract mb_* functions
        $substr = function_exists('mb_substr') ? 'mb_substr' : 'substr';
        $strlen = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';

        //Read that stuff chunk by chunk (whatever buffersize there is)
        $result = $substr($this->data, $this->position, $length);
        $this->position += $strlen($result);

        return $result;
    }

    /**
     * Gets called when someone calls something like ftell on this stream.
     *
     * Returns the current position in our stream
     *
     * @return int
     */
    public function stream_tell()
    {

        return $this->position;
    }

    /**
     * Gets called when someone calls something like feof on this stream.
     *
     * Returns true, if the stream is at the end of data or false, if there's
     * still PHTML left to read
     *
     * @return bool
     */
    public function stream_eof()
    {

        return $this->position >= $this->length;
    }

    /**
     * This gets called when someone calls fstat on this stream.
     *
     * It's a requirement to define stream_stat() for some functions
     * We just return an empty array
     *
     * @return array
     */
    public function stream_stat()
    {

        return [];
    }

    /**
     * Checks if a stream wrapper with the given scheme/name is registered.
     *
     * @see stream_get_wrappers
     *
     * @param string $name The scheme/name of the stream wrapper
     *
     * @return bool
     */
    public static function isRegistered($name)
    {

        return in_array($name, stream_get_wrappers());
    }

    /**
     * Registers this class a a stream wrapper with the given scheme/name.
     *
     * @see stream_wrapper_register
     *
     * @param string $name The scheme/name this wrapper should react on
     */
    public static function register($name)
    {

        if (self::isRegistered($name))
            throw new RuntimeException(
                "The stream wrapper is already registered as $name"
            );

        stream_wrapper_register($name, self::class);
    }

    /**
     * Unregisters the stream wrapper with the given scheme/name.
     *
     * @see stream_wrapper_unregister
     *
     * @param string $name The scheme/name of the stream wrapper to be unregistered
     */
    public static function unregister($name)
    {

        if (!self::isRegistered($name))
            throw new RuntimeException(
                "The stream wrapper is already registered not $name"
            );

        stream_wrapper_unregister($name);
    }
}