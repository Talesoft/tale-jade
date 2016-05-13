<?php
/**
 * The Tale Jade Renderer-Adapter Prototype.
 *
 * All adapters that the renderer can use should extend this class
 * and implement its ->render() method.
 *
 * This file is part of the Tale Jade Template Engine for PHP
 *
 * LICENSE:
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * @category   Presentation
 * @package    Tale\Jade\Renderer
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/files/Renderer.AdapterBase.html
 * @since      File available since Release 1.0
 */

namespace Tale\Jade\Renderer;

use Tale\Jade\Renderer;
use Tale\ConfigurableTrait;

/**
 * Acts as a base class for renderer adapters.
 *
 * Provides some requirements for the renderer adapters.
 * The key is the ->render() method with actually
 * does the rendering.
 *
 * @category   Presentation
 * @package    Tale\Jade\Renderer
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/classes/Tale.Jade.Renderer.AdapterBase.html
 * @since      File available since Release 1.0
 */
abstract class AdapterBase
{
    use ConfigurableTrait;

    /**
     * The renderer this adapter got created in.
     *
     * @var Renderer
     */
    private $renderer;


    /**
     * Creates a new adapter.
     *
     * If you create a child-adapter, make sure to set your default options correctly
     *
     * Something along these lines:
     * <code>
     *    parent::__construct($renderer, array_replace_recursive([
     *        'your' => 'default',
     *        'option' => 'array'
     *    ], $options ? $options : []);
     * </code>
     *
     * @param Renderer   $renderer the renderer this adapter is created in
     * @param array|null $options  the options array for the adapter
     */
    public function __construct(Renderer $renderer, array $options = null)
    {

        $this->renderer = $renderer;

        if ($options)
            $this->setOptions($options);
    }

    /**
     * Returns the attached renderer this adapter was created in.
     *
     * @return Renderer
     */
    public function getRenderer()
    {

        return $this->renderer;
    }

    /**
     * Renders a jade file by a given path.
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
     * @param string     $path the relative path to be rendered
     * @param array|null $args the variables for the template
     *
     * @return string the rendered markup
     */
    abstract public function render($path, array $args = null);
}