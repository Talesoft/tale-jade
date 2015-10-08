<?php
/**
 * The Tale Jade Project
 *
 * The Renderer AdapterBase class
 *
 * This file is part of the Tale Jade Template Engine for PHP
 *
 * @author Torben Köhn <tk@talesoft.io>
 * @author Talesoft <info@talesoft.io>
 * @projectGroup Tale
 * @project Jade
 * @component Renderer\AdapterBase
 *
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * Please do not remove this comment block.
 * Thank you and have fun with Tale Jade!
 */

namespace Tale\Jade\Renderer;

use Tale\Jade\Renderer;

/**
 * Acts as a base class for renderer adapters
 *
 * Provides some requirements for the renderer adapters
 *
 * @package Tale\Jade\Renderer
 */
abstract class AdapterBase
{

    /**
     * The renderer this adapter got created in
     * @var \Tale\Jade\Renderer
     */
    private $_renderer;

    /**
     * The options array for this adapter
     * @var array
     */
    private $_options;

    /**
     * Creates a new adapter
     *
     * If you create a child-adapter, make sure to set your default options correctly
     *
     * Something along these lines:
     * parent::__construct($renderer, array_replace_recursive([
     *    'your' => 'default',
     *    'option' => 'array'
     * ], $options ? $options : []);
     *
     * @param \Tale\Jade\Renderer $renderer The renderer this adapter is created in
     * @param array|null          $options  The options array for the adapter
     */
    public function __construct(Renderer $renderer, array $options = null)
    {

        $this->_renderer = $renderer;
        $this->_options = $options ? $options : [];
    }

    /**
     * Returns the attached renderer this adapter was created in
     *
     * @return \Tale\Jade\Renderer
     */
    public function getRenderer()
    {

        return $this->_renderer;
    }

    /**
     * Returns an option by a given name
     * The existence of the option is not checked
     *
     * @param string $name The name of the option
     *
     * @return mixed The value of the option
     */
    protected function getOption($name)
    {

        return $this->_options[$name];
    }

    /**
     * Renders a jade file by a given path
     *
     * The extension can be omitted if it's the extension
     * set in the Compiler-options ('.jade' by default)
     *
     * The given $args-argument should be an associative array
     * and will be passed as variables
     * that you can use inside the rendered template file
     *
     * Notice that the path is relative to the Compiler-option 'paths'
     * or, if no paths passed, the paths in get_include_path()
     *
     * You might just echo the result, cache it or do anything else with it
     *
     * @param string     $path The relative path to be rendered
     * @param array|null $args The variables for the template
     *
     * @return string The rendered markup
     */
    abstract public function render($path, array $args = null);
}