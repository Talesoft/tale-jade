<?php
/**
 * The Tale Jade Config Utility Trait.
 *
 * Containts a trait that eases up configuration array handling
 * for all tale jade components
 *
 * LICENSE:
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * @category   Presentation
 * @package    Tale\Jade\Util
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Talesoft (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.3.5
 * @link       http://jade.talesoft.io/docs/files/Util/Compiler.html
 * @since      File available since Release 1.3.5
 */

namespace Tale\Jade\Util;

/**
 * Provides some utility methods to work with configuration arrays
 *
 * @package Tale\Jade\Util
 */
trait ConfigurableTrait
{

    /**
     * The options array.
     *
     * Keys are option names, values are option values
     * @var array
     */
    private $_options = [];

    /**
     * Sets the options initially providing default- and optional user options.
     *
     * @param array      $defaults    the default options
     * @param array|null $userOptions the optional options passed by the user
     */
    public function defineOptions(array $defaults, array $userOptions = null)
    {

        $this->setOptions($defaults);

        if ($userOptions)
            $this->setOptions($userOptions, true);
    }

    /**
     * Returns the option array.
     *
     * @return array
     */
    public function getOptions()
    {

        return $this->_options;
    }

    /**
     * Returns a single option by its name.
     *
     * You can pass an optional default value (Default: null)
     *
     * @param string $name     the name of the option to return
     * @param mixed $default  the default value if the option is not set (Default: null)
     *
     * @return mixed
     */
    public function getOption($name, $default = null)
    {

        return isset($this->_options[$name]) ? $this->_options[$name] : $default;
    }

    /**
     * Merges the current options with another option array.
     *
     * The second parameter makes this recursive.
     * The functions used are array_replace and array_replace_recursive
     *
     * Passing the third parameter reverses the merge, so you don't overwrite
     * passed options with existing one, but rather set them only if they
     * don't exist yet (defaulting)
     *
     * @param array $options    the options to merge with
     * @param bool  $recursive  should we merge recursively or not
     * @param bool  $reverse    should values be prepended rather than appended
     *
     * @return $this
     */
    public function setOptions(array $options, $recursive = false, $reverse = false)
    {

        $merge = 'array_replace';

        if ($recursive)
            $merge .= '_recursive';

        $this->_options = $reverse
                        ? $merge($options, $this->_options)
                        : $merge($this->_options, $options);

        return $this;
    }

    /**
     * Replaces with all options passed, if they are not set yet.
     *
     * This is an alias to ->setOptions with the third parameter set
     * to true.
     *
     * @param array      $defaults   the array of default options
     * @param bool|false $recursive  should we merge recursively or not
     *
     * @return $this
     */
    public function setDefaults(array $defaults, $recursive = false)
    {

        return $this->setOptions($defaults, $recursive, true);
    }

    /**
     * Sets a single option to the passed value.
     *
     * @param string $name the name of the option
     * @param mixed $value the value of the option
     *
     * @return $this
     */
    public function setOption($name, $value)
    {

        $this->_options[$name] = $value;

        return $this;
    }

    /**
     * Forwards an option to an option array.
     *
     * e.g.
     * options = [
     *      'target' => [
     *          'targetName' => null
     *      ],
     *
     *      'someOption' => 'someValue'
     * ]
     *
     * options->forwardOption('someOption', 'target', 'targetName')
     * will set ['target']['targetName'] to 'someValue'
     *
     * Notice that the third parameter can be omitted, it will
     * be set to the same name as the first parameter then.
     *
     * @param string $name       the name of the option to forward
     * @param string $target     the name of the option array to forward to
     * @param string $targetName the name of the target option name inside the target array
     */
    public function forwardOption($name, $target, $targetName = null)
    {

        $targetName = $targetName ? $targetName : $name;

        if (isset($this->_options[$name]))
            $this->_options[$target][$targetName] = $this->_options[$name];
    }
}