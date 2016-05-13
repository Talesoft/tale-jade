<?php
/**
 * The Tale Jade File Renderer-Adapter.
 *
 * This adapter uses a Cache Directory and PHTML-files to render
 * the generated markup.
 *
 * Make sure the Cache Directory is writable!
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
 * @link       http://jade.talesoft.io/docs/files/Renderer.Adapter.File.html
 * @since      File available since Release 1.0
 */

namespace Tale\Jade\Renderer\Adapter;

use RuntimeException;
use Tale\Jade\Renderer;
use Tale\Jade\Renderer\AdapterBase;

/**
 * Handles rendering with cached files and include.
 *
 * This is the best adapter for production systems, cheap VPS or any hosts
 * that don't have 'allow_url_fopen' activated
 *
 * The following happens:
 * 1. The jade gets rendered to markup
 * 2. The markup is saved into a .phtml-file
 * 3. The .phtml-file is included
 *
 * Advantages:
 * - Caching integrated
 * - Next to memory-caching probably the fastest way to render
 * - Good debugging, especially then compiler:pretty is activated
 * - No special configuration needed
 *
 * Disadvantages:
 * - You need a cache-directory (Though, most websites have one anyways)
 * - It needs some configuration in the most cases
 * - Cache files should be secured, since it's PHP-code!!! (e.g. Deny in .htaccess)
 *
 * @category   Presentation
 * @package    Tale\Jade\Renderer\Adapter
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/classes/Tale.Jade.Renderer.Adapter.File.html
 * @since      File available since Release 1.0
 */
class File extends AdapterBase
{

    /**
     * Creates a new File renderer adapter.
     *
     * If the cache directory doesn't exist, it tries to automatically create it
     *
     * Possible options are:
     * path:        The path where rendered files are stored
     * extension:   The extension we should store the files with (Default: .phtml)
     * lifeTime:    The Cache lifeTime (Set to 0 to disable cache), (Default: 0)
     *
     * @param Renderer   $renderer the renderer instance this renderer was created in
     * @param array|null $options  the options array for this renderer adapter
     *
     * @throws \Exception when the Cache Directory is not writable
     */
    public function __construct(Renderer $renderer, array $options = null)
    {

        parent::__construct($renderer, $options);

        $this->setDefaults([
            'path'      => './cache/views',
            'extension' => '.phtml',
            'ttl'  => 0
        ]);

        $dir = $this->getOption('path');

        //Automatically create directory if it doesn't exist (or try to do so)
        if (!is_dir($dir)) {

            @mkdir($dir, 0775, true);

            if (!is_dir($dir))
                throw new RuntimeException("Failed to create output directory $dir");
        }

        //Make sure we can write to it
        if (!is_writable($dir))
            throw new RuntimeException("Output directory $dir not writable");
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
     * @throws \Exception when the directory can't be created
     * @throws \Tale\Jade\Compiler\Exception when the file to render wasnt found
     */
    public function render($path, array $args = null)
    {

        $compilerOptions = $this->getRenderer()->getCompiler()->getOptions();
        $exts = $compilerOptions['extensions'];

        foreach ($exts as $ext) {

            if (substr($path, -strlen($ext)) === $ext) {

                $path = substr($path, 0, -strlen($ext));
                break;
            }
        }

        $outputPath = rtrim($this->getOption('path'), '/\\').'/'.ltrim($path.$this->getOption('extension'), '/\\');

        $render = function ($__path, $__args) {

            ob_start();
            extract($__args);
            include($__path);

            return ob_get_clean();
        };

        if (!file_exists($outputPath) || time() - filemtime($outputPath) >= $this->getOption('ttl')) {

            $dir = dirname($outputPath);

            if (!is_dir($dir)) {

                @mkdir($dir, 0775, true);

                if (!is_dir($dir))
                    throw new RuntimeException(
                        "Failed to create directory $dir"
                    );
            }

            file_put_contents($outputPath, $this->getRenderer()->compileFile($path));
        }

        return $render($outputPath, $args ? $args : []);
    }
}