<?php

namespace Tale\Jade\Compiler;

class Resolver
{

    private $paths;

    public function __construct(array $paths = null)
    {

        $this->paths = $paths ?: explode(PATH_SEPARATOR, get_include_path());
    }

    public function pushPath($path)
    {

        $this->paths[] = $path;

        return $this;
    }

    public function popPath()
    {

        array_pop($this->paths);

        return $this;
    }

    public function resolve($fileName, array $extensions = null)
    {

        $extensions = $extensions ?: [];

        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        if (!empty($ext) && in_array($ext, $extensions)) {

            $extensions = [$ext];
            $fileName = dirname($fileName).'/'.basename($fileName, ".$ext");
        }

        foreach ($this->paths as $path) {
            foreach ($extensions as $ext) {

                $extFileName = "$fileName.$ext";
                $fullPath = realpath(rtrim($path, '/\\').'/'.ltrim($extFileName, '/\\'));

                if ($fullPath)
                    return $fullPath;
            }
        }

        return null;
    }
}