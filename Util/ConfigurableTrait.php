<?php

namespace Tale\Jade\Util;

trait ConfigurableTrait
{

    private $_options = [];

    public function defineOptions(array $defaults, array $userOptions = null)
    {

        $this->setOptions($defaults);

        if ($userOptions)
            $this->setOptions($userOptions, true);
    }

    public function getOptions()
    {

        return $this->_options;
    }

    public function getOption($name, $default = null)
    {

        return isset($this->_options[$name]) ? $this->_options[$name] : $default;
    }

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

    public function setDefaults(array $defaults, $recursive = false)
    {

        return $this->setOptions($defaults, $recursive, true);
    }

    public function setOption($name, $value)
    {

        $this->_options[$name] = $value;

        return $this;
    }

    public function forwardOption($name, $target, $targetName = null)
    {

        $targetName = $targetName ? $targetName : $name;

        if (isset($this->_options[$name]))
            $this->_options[$target][$targetName] = $this->_options[$name];
    }
}