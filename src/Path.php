<?php

namespace Orryv;

use InvalidArgumentException;
use Orryv\Path\DirectoryPath;
use Orryv\Path\Enums\PathFormat;
use Orryv\Path\FilePath;
use Orryv\Path\FilesystemPath;
use Orryv\Path\Support\FilesystemParser;
use Orryv\Path\Support\FilesystemRenderer;
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

    public static function dot(string $value, PathFormat $format = PathFormat::ACCESS_PATH): FilesystemPath
    {
        $data = FilesystemParser::parse($value, $format);

        $hasDirectoryHint = self::hasDirectoryHint($value, $format);

        $lastSegment = $data->segments[count($data->segments) - 1] ?? '';
        $isFile = !$hasDirectoryHint && $lastSegment !== '' && str_contains($lastSegment, '.');

        if ($isFile) {
            return FilePath::fromData($data);
        }

        $preserve = self::shouldPreserveTrailingSlash($value, $format);

        return DirectoryPath::fromData($data, $preserve);
    }

    public static function system(string $value, PathFormat $format = PathFormat::ACCESS_PATH): FilesystemPath
    {
        $data = FilesystemParser::parse($value, $format);

        $fileCandidate = FilesystemRenderer::toAccessPath($data, false, false);
        $directoryCandidate = FilesystemRenderer::toAccessPath($data, true, true);

        if (is_dir($fileCandidate) || is_dir($directoryCandidate)) {
            $preserve = self::shouldPreserveTrailingSlash($value, $format);

            return DirectoryPath::fromData($data, $preserve);
        }

        if (is_file($fileCandidate) || file_exists($fileCandidate)) {
            return FilePath::fromData($data);
        }

        throw new InvalidArgumentException('Path does not exist on the filesystem.');
    }

    public static function url(string $value, PathFormat $format = PathFormat::ACCESS_URI): UrlPath
    {
        return UrlPath::from($value, $format);
    }

    private static function hasDirectoryHint(string $value, PathFormat $format): bool
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return false;
        }

        if (str_ends_with($trimmed, '/') || str_ends_with($trimmed, '\\')) {
            return true;
        }

        if ($format === PathFormat::ACCESS_URI) {
            $path = parse_url($trimmed, PHP_URL_PATH) ?? '';

            return $path !== '' && str_ends_with($path, '/');
        }

        return false;
    }

    private static function shouldPreserveTrailingSlash(string $value, PathFormat $format): bool
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return true;
        }

        if (str_ends_with($trimmed, '/') || str_ends_with($trimmed, '\\')) {
            return true;
        }

        if ($format === PathFormat::ACCESS_URI) {
            $path = parse_url($trimmed, PHP_URL_PATH) ?? '';

            return $path !== '' && str_ends_with($path, '/');
        }

        return false;
    }
}
