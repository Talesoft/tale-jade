<?php

namespace Tale\Jade\Renderer\Adapter;

use Tale\Jade\Renderer;
use Tale\Jade\Renderer\AdapterBase;
use Tale\Util\PathUtil;

class File extends AdapterBase
{

    public function __construct(Renderer $renderer, array $options = null)
    {

        parent::__construct($renderer, array_replace_recursive([
            'path' => './cache/views',
            'extension' => '.phtml',
            'lifeTime' => 3600
        ], $options ? $options : []));

        $dir = $this->getOption('path');
        if (!is_dir($dir)) {

            @mkdir($dir, 0775, true);

            if (!is_dir($dir))
                throw new \Exception("Failed to create output directory $dir");
        }

        if (!is_writable($dir))
            throw new \Exception("Output directory not writable $dir");
    }

    public function render($path, array $args = null)
    {

        $compilerOptions = $this->getRenderer()->getCompiler()->getOptions();
        $ext = $compilerOptions['extension'];

        if (substr($path, -strlen($ext)) === $ext)
            $path = substr($path, 0, -strlen($ext));

        $outputPath = rtrim($this->getOption('path'), '/\\').'/'.ltrim($path.$this->getOption('extension'), '/\\');

        $render = function($__path, $__args) {

            ob_start();
            extract($__args);
            include($__path);

            return ob_get_clean();
        };

        if (!file_exists($outputPath) || time() - filemtime($outputPath) >= $this->getOption('lifeTime')) {

            $dir = dirname($outputPath);

            if (!is_dir($dir)) {

                @mkdir($dir, 0775, true);

                if (!is_dir($dir))
                    throw new \Exception(
                        "Failed to create directory $dir"
                    );
            }

            file_put_contents($outputPath, $this->getRenderer()->compileFile($path));
        }

        return $render($outputPath, $args ? $args : []);
    }
}