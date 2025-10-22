<?php

namespace Orryv\Path\Support;

final class FilesystemRenderer
{
    private const WINDOWS_LONG_LIMIT = 260;

    private function __construct()
    {
    }

    public static function toReferencePath(FilesystemPathData $data, bool $isDirectory, bool $preserveTrailingSlash): string
    {
        $segments = self::escapedSegments($data);

        if ($data->segments === []) {
            if ($isDirectory) {
                return $preserveTrailingSlash ? $data->root : self::trimRoot($data);
            }

            return self::trimRoot($data);
        }

        $path = $data->root . implode('/', $segments);

        if ($isDirectory) {
            return $preserveTrailingSlash ? $path . '/' : $path;
        }

        return $path;
    }

    public static function toAccessPath(FilesystemPathData $data, bool $isDirectory, bool $preserveTrailingSlash): string
    {
        $segments = self::escapedSegments($data, $data->isWindowsDrive || $data->isUnc);

        if ($data->isWindowsDrive) {
            $path = rtrim(str_replace('/', '\\', $data->root), '\\'); // C:
            if ($segments !== []) {
                $path .= '\\' . implode('\\', $segments);
            }
        } elseif ($data->isUnc) {
            $parts = explode('/', trim(substr($data->root, 2), '/'));
            $server = $parts[0] ?? '';
            $share = $parts[1] ?? '';
            $prefixCount = max(1, $data->uncPrefixLength);
            if (!$data->longPathPreference && $data->hadLongPathPrefix) {
                $prefixCount = 1;
            }
            $path = str_repeat('\\', $prefixCount) . $server;
            if ($share !== '') {
                $path .= '\\' . $share;
            }
            if ($segments !== []) {
                $path .= '\\' . implode('\\', $segments);
            }
        } else {
            $path = '/';
            if ($segments !== []) {
                $path .= implode('/', $segments);
            }
        }

        if ($isDirectory) {
            if ($preserveTrailingSlash) {
                $path = self::ensureTrailingSeparator($path, $data);
            } else {
                $path = self::removeTrailingSeparator($path, $data);
            }
        } else {
            $path = self::removeTrailingSeparator($path, $data);
        }

        if ($data->isWindowsDrive || $data->isUnc) {
            $basePath = $path;
            if ($data->isUnc) {
                $basePath = ltrim($path, '\\');
            }

            $needsPrefix = $data->longPathPreference && (
                $data->hadLongPathPrefix || self::isOverLongLimit($basePath)
            );

            if ($needsPrefix) {
                $slashCount = $data->longPathPrefixSlashes > 0 ? $data->longPathPrefixSlashes : 2;
                $prefix = str_repeat('\\', max(1, $slashCount)) . '?\\';

                if ($data->isUnc) {
                    return $prefix . 'UNC\\' . $basePath;
                }

                return $prefix . ltrim($basePath, '\\');
            }
        }

        if ($data->isWindowsDrive && $data->preferForwardSlash) {
            return str_replace('\\', '/', $path);
        }

        return $path;
    }

    public static function toAccessUri(FilesystemPathData $data, bool $isDirectory, bool $preserveTrailingSlash): string
    {
        $encodedSegments = array_map('rawurlencode', $data->segments);
        $suffix = implode('/', $encodedSegments);

        if ($data->isWindowsDrive) {
            $drive = substr($data->root, 0, 2);
            $uri = 'file:///' . $drive;
            if ($suffix !== '') {
                $uri .= '/' . $suffix;
            }

            if ($isDirectory && ($preserveTrailingSlash || $suffix === '')) {
                $uri .= '/';
            }

            return $uri;
        }

        if ($data->isPosix) {
            $uri = 'file:///';
            if ($suffix !== '') {
                $uri .= $suffix;
            }

            if ($isDirectory && ($preserveTrailingSlash || $suffix === '')) {
                $uri .= '/';
            }

            return $uri;
        }

        // UNC path
        $parts = explode('/', trim(substr($data->root, 2), '/'));
        $server = $parts[0] ?? '';
        $share = $parts[1] ?? '';

        $uri = 'file://' . $server;
        if ($share !== '') {
            $uri .= '/' . rawurlencode($share);
        }

        if ($suffix !== '') {
            $uri .= '/' . $suffix;
        }

        if ($isDirectory) {
            $uri .= '/';
        } elseif ($suffix === '') {
            $uri .= '/';
        }

        return $uri;
    }

    private static function ensureTrailingSeparator(string $path, FilesystemPathData $data): string
    {
        $separator = $data->isWindowsDrive || $data->isUnc ? '\\' : '/';
        if (!str_ends_with($path, $separator)) {
            return $path . $separator;
        }

        return $path;
    }

    private static function removeTrailingSeparator(string $path, FilesystemPathData $data): string
    {
        $separator = $data->isWindowsDrive || $data->isUnc ? '\\' : '/';
        $trimmed = rtrim($path, $separator);

        if ($trimmed === '') {
            return $data->isPosix ? '/' : ($data->isUnc ? '\\' . ltrim($path, '\\') : rtrim(str_replace('/', '\\', $data->root), '\\'));
        }

        if ($data->isUnc && !str_starts_with($trimmed, '\\')) {
            $parts = explode('/', trim(substr($data->root, 2), '/'));
            $server = $parts[0] ?? '';
            $share = $parts[1] ?? '';
            if ($trimmed === $server . '\\' . $share) {
                return '\\' . $trimmed;
            }
        }

        if ($data->isWindowsDrive && !str_contains($trimmed, '\\')) {
            return $trimmed;
        }

        return $trimmed;
    }

    private static function trimRoot(FilesystemPathData $data): string
    {
        if ($data->isPosix) {
            return '/';
        }

        return rtrim($data->root, '/');
    }

    private static function isOverLongLimit(string $path): bool
    {
        return mb_strlen($path, 'UTF-8') > self::WINDOWS_LONG_LIMIT;
    }

    /**
     * @return list<string>
     */
    private static function escapedSegments(FilesystemPathData $data, bool $forceNonAsciiEscape = false): array
    {
        $segments = [];
        foreach ($data->segments as $index => $segment) {
            $shouldEscape = $data->normalizedFlags[$index] ?? false;
            if (!$shouldEscape && $forceNonAsciiEscape && self::containsNonAscii($segment)) {
                $shouldEscape = true;
            }

            $segments[] = Unicode::escapeSegment($segment, $shouldEscape);
        }

        return $segments;
    }

    private static function containsNonAscii(string $value): bool
    {
        return preg_match('/[^\x00-\x7F]/u', $value) === 1;
    }
}
