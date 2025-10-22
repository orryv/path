<?php

namespace Orryv;

use Orryv\Path\DirectoryPath;
use Orryv\Path\Enums\PathFormat;
use Orryv\Path\FilePath;
use Orryv\Path\UrlPath;

final class Path
{
    private function __construct()
    {
    }

    public static function file(string $value, PathFormat $format = PathFormat::ACCESS_PATH): FilePath
    {
        return FilePath::from($value, $format);
    }

    public static function dir(string $value, PathFormat $format = PathFormat::ACCESS_PATH): DirectoryPath
    {
        return DirectoryPath::from($value, $format);
    }

    public static function url(string $value, PathFormat $format = PathFormat::ACCESS_URI): UrlPath
    {
        return UrlPath::from($value, $format);
    }
}
