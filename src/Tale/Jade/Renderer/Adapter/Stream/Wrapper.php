<?php

namespace Tale\Jade\Renderer\Adapter\Stream;

class Wrapper
{

    const BUFFER_SIZE = 2048;

    private $_data;
    private $_position;
    private $_length;

    public function stream_open($url, $mode, $options, &$opened_path) {

        $substr = function_exists('mb_substr') ? 'mb_substr' : 'substr';
        $strlen = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
        $strpos = function_exists('mb_strpos') ? 'mb_strpos' : 'strpos';

        $this->_data = base64_decode($substr($url, $strpos($url, ';') + 1));

        $this->_position = 0;
        $this->_length = $strlen($this->_data);

        return true;
    }
    public function stream_read($length) {

        $substr = function_exists('mb_substr') ? 'mb_substr' : 'substr';
        $strlen = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';

        $result = $substr($this->_data, $this->_position, $length);
        $this->_position += $strlen($result);

        return $result;
    }

    public function stream_tell() {

        return $this->_position;
    }

    public function stream_eof() {

        return $this->_position >= $this->_length;
    }

    public function stream_stat()
    {

        return [];
    }

    public static function isRegistered($name)
    {

        return in_array($name, stream_get_wrappers());
    }

    public static function register($name)
    {

        stream_wrapper_register($name, self::class);
    }

    public static function unregister($name)
    {

        stream_wrapper_unregister($name);
    }
}