<?php

namespace Orryv\Path;

class Utils
{
    /**
     * Split the path into an array and remove leading/trailing slashes. (empty elements are removed)
     * 
     * @param string $path Reference path only: path/only, so no /, C:/, ... and forward slashes only
     */
    public static function splitPathAndTrimSlashes(string $path, $preserve_last_slash = false): array
    {
        $path = explode('/', $path);

        // Remove leading/trailing slashes
        if($path[0] === '') {
            array_shift($path);
        }

        if(!$preserve_last_slash && $path[count($path) - 1] === '') {
            array_pop($path);
        }

        return $path;
    }
}

?>