<?php
/**
 * The Tale Jade Stream Renderer-Adapter.
 *
 * This adapter uses PHP Stream Wrappers to realize a clutter-less,
 * RAM-based rendering approach.
 *
 * Notice that the php.ini-setting `allow_url_fopen` should be `On`
 *
 * This file is part of the Tale Jade Template Engine for PHP
 *
 * LICENSE:
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * @category   Presentation
 * @package    Tale\Jade\Renderer\Adapter
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/files/Renderer.Adapter.Stream.html
 * @since      File available since Release 1.0
 */

namespace Tale\Jade\Renderer\Adapter;

use Tale\Jade\Renderer;
use Tale\Jade\Renderer\Adapter\Stream\Wrapper;
use Tale\Jade\Renderer\AdapterBase;

/**
 * Handles rendering via Data-URIs and a PHP Stream Wrapper.
 *
 * Notice that this only works, if the php.ini setting 'allow_url_fopen' is ON
 * This works through including the PHTML file as an URI
 *
 * It's safe to use! Everything happens locally.
 *
 * The following happens:
 * 1. The jade gets rendered to markup
 * 2. The markup-string is base64_encode'd
 * 3. A data URI is put together that looks like this: 'jade-phtml://data;<base64-encoded-markup>
 * 4. The data URI is include'd
 *
 * We register a PHP Stream Wrapper for the 'jade-phtml'-scheme
 * that does the following:
 * 1. Strip everything until after the ';' in the URL
 * 2. base64_decode that shit
 * 3. Return the PHTML
 *
 * This way, the PHTML acts like a normal PHP file
 *
 * Advantages:
 * - Quick to set up
 * - Easy to use
 * - No configuration needed
 * - May or may not be easier to cache (depending on the cache system)
 *
 * Disadvantages:
 * - No debugging the PHTML at all. It's a base64-encoded string.
 * - It may be slower than a usual file-adapter through the encoding
 * - May or may not be easier to cache (depending on the cache system)
 *
 * @category   Presentation
 * @package    Tale\Jade\Renderer\Adapter
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/classes/Tale.Jade.Renderer.Adapter.Stream.html
 * @since      File available since Release 1.0
 */
class Stream extends AdapterBase
{

    /**
     * Creates a new stream adapter.
     *
     * Possible options are:
     * scheme: The scheme to register the wrapper as (default: 'jade-phtml')
     *
     * If the stream wrapper with the given name is not registered yet,
     * it is registered
     * The stream wrapper used is \Tale\Jade\Renderer\Adapter\Stream\Wrapper
     *
     * @param Renderer   $renderer the renderer instance this adapter was created in
     * @param array|null $options  an array of options for the adapter
     */
    public function __construct(Renderer $renderer, array $options = null)
    {

        parent::__construct($renderer, $options);

        $this->setDefaults(['scheme' => 'jade-phtml']);

        if (!Wrapper::isRegistered($this->getOption('scheme')))
            Wrapper::register($this->getOption('scheme'));
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
    public function render($path, array $args = null)
    {

        $compiled = base64_encode($this->getRenderer()->compileFile($path));
        $url = $this->getOption('scheme').'://data;'.$compiled;


        $render = function ($__dataUrl, $__args) {

            ob_start();
            extract($__args);
            include($__dataUrl);

            return ob_get_clean();
        };

        return $render($url, $args ? $args : []);
    }
}