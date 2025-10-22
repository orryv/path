<?php

namespace Orryv\Path\Support;

use Orryv\Path\Enums\PathFormat;
use InvalidArgumentException;

final class FilesystemParser
{
    private const WINDOWS_UNC_LONG_PREFIX = 'UNC\\';

    private function __construct()
    {
    }

    public static function parse(string $value, PathFormat $format): FilesystemPathData
    {
        return match ($format) {
            PathFormat::ACCESS_PATH => self::fromAccessPath($value),
            PathFormat::REFERENCE_PATH => self::fromReferencePath($value),
            PathFormat::ACCESS_URI => self::fromAccessUri($value),
        };
    }

    private static function fromAccessPath(string $value): FilesystemPathData
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException('Path cannot be empty.');
        }

        $hadLongPrefix = false;
        $longPreference = false;
        $uncPrefixLength = 0;
        $longPrefixSlashes = 0;
        $isUnc = false;

        $leadingSlashes = strspn($value, '\\');
        if (($leadingSlashes === 1 || $leadingSlashes === 2) && ($value[$leadingSlashes] ?? '') === '?' && ($value[$leadingSlashes + 1] ?? '') === '\\') {
            $longPrefixSlashes = $leadingSlashes;
            $hadLongPrefix = true;
            $longPreference = true;
            $value = substr($value, $leadingSlashes + 2);

            if (str_starts_with($value, self::WINDOWS_UNC_LONG_PREFIX)) {
                $isUnc = true;
                $value = substr($value, strlen(self::WINDOWS_UNC_LONG_PREFIX));
                $uncPrefixLength = max(2, $longPrefixSlashes);
            }
        }

        if (!$isUnc && str_starts_with($value, self::WINDOWS_UNC_LONG_PREFIX)) {
            $isUnc = true;
            $value = substr($value, strlen(self::WINDOWS_UNC_LONG_PREFIX));
            $uncPrefixLength = 2;
        }

        if (!$isUnc) {
            if ($value !== '' && ($value[0] === '\\' || $value[0] === '/')) {
                $prefixChar = $value[0];
                $count = strspn($value, $prefixChar);
                if ($prefixChar === '/' && $count < 2) {
                    $count = 0;
                }

                if ($count > 0) {
                    $isUnc = true;
                    $uncPrefixLength = min(2, $count);
                    $value = substr($value, $count);
                }
            }
        }

        if ($isUnc) {
            $normalized = str_replace('\\', '/', $value);
            $parts = explode('/', $normalized);
            $server = array_shift($parts) ?? '';
            $share = array_shift($parts) ?? '';

            $root = '//' . $server . '/' . $share . '/';
            [$segments, $flags] = self::normalizeSegments($parts);

            $prefix = max(1, $uncPrefixLength);

            return new FilesystemPathData(
                $root,
                $segments,
                false,
                true,
                false,
                $longPreference,
                $hadLongPrefix,
                $flags,
                $prefix,
                false,
                $longPrefixSlashes,
            );
        }

        if (self::isWindowsDriveAccess($value)) {
            $drive = strtoupper($value[0]);
            $rest = substr($value, 2);
            $rest = str_replace('\\', '/', $rest);
            [$segments, $flags] = self::normalizeSegments(explode('/', ltrim($rest, '/')));

            $root = $drive . ':/';

            return new FilesystemPathData(
                $root,
                $segments,
                true,
                false,
                false,
                $longPreference,
                $hadLongPrefix,
                $flags,
                0,
                false,
                $longPrefixSlashes,
            );
        }

        if (str_starts_with($value, '/')) {
            [$segments, $flags] = self::normalizeSegments(explode('/', ltrim($value, '/')));

            return new FilesystemPathData('/', $segments, false, false, true, $longPreference, $hadLongPrefix, $flags);
        }

        throw new InvalidArgumentException('Only absolute paths are supported.');
    }

    private static function fromReferencePath(string $value): FilesystemPathData
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException('Path cannot be empty.');
        }

        if (str_starts_with($value, '//')) {
            $value = substr($value, 2);
            $parts = explode('/', $value);
            $server = array_shift($parts) ?? '';
            $share = array_shift($parts) ?? '';
            $root = '//' . $server . '/' . $share . '/';
            [$segments, $flags] = self::normalizeSegments($parts);

            return new FilesystemPathData($root, $segments, false, true, false, false, false, $flags, 1);
        }

        if (self::isWindowsDriveReference($value)) {
            $drive = strtoupper($value[0]);
            $rest = substr($value, 2);
            [$segments, $flags] = self::normalizeSegments(explode('/', ltrim($rest, '/')));
            $root = $drive . ':/';

            return new FilesystemPathData($root, $segments, true, false, false, false, false, $flags);
        }

        if (str_starts_with($value, '/')) {
            [$segments, $flags] = self::normalizeSegments(explode('/', ltrim($value, '/')));

            return new FilesystemPathData('/', $segments, false, false, true, false, false, $flags);
        }

        throw new InvalidArgumentException('Only absolute reference paths are supported.');
    }

    private static function fromAccessUri(string $value): FilesystemPathData
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException('Path cannot be empty.');
        }

        $parts = parse_url($value);
        if ($parts === false || ($parts['scheme'] ?? '') !== 'file') {
            throw new InvalidArgumentException('Only file:// URIs can be converted to filesystem paths.');
        }

        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '';
        $path = rawurldecode($path);
        $normalizedPath = $path;
        if ($normalizedPath !== '' && !str_starts_with($normalizedPath, '/')) {
            $normalizedPath = '/' . $normalizedPath;
        }

        if ($host === '') {
            // Could be Windows drive or POSIX path
            if (preg_match('#^/[A-Za-z]:/#', $normalizedPath) === 1) {
                $drive = strtoupper($normalizedPath[1]);
                $rest = substr($normalizedPath, 3);
                [$segments, $flags] = self::normalizeSegments(explode('/', ltrim($rest, '/')));

                return new FilesystemPathData($drive . ':/', $segments, true, false, false, false, false, $flags);
            }

            [$segments, $flags] = self::normalizeSegments(explode('/', ltrim($normalizedPath, '/')));

            return new FilesystemPathData('/', $segments, false, false, true, false, false, $flags);
        }

        // UNC path via file://host/share
        [$segments, $flags] = self::normalizeSegments(explode('/', ltrim($normalizedPath, '/')));
        if ($segments === []) {
            $root = '//' . $host . '/';
        } else {
            $share = array_shift($segments);
            array_shift($flags);
            $root = '//' . $host . '/' . $share . '/';
        }

        $flags = array_values($flags);

        return new FilesystemPathData($root, $segments, false, true, false, false, false, $flags, 1);
    }

    /**
     * @param array<int, string> $segments
     * @return array{0:list<string>,1:list<bool>}
     */
    private static function normalizeSegments(array $segments): array
    {
        $normalized = [];
        $flags = [];
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === false) {
                continue;
            }

            [$value, $changed] = Unicode::normalizeSegment($segment);
            $normalized[] = $value;
            $flags[] = $changed;
        }

        return [$normalized, $flags];
    }

    private static function isWindowsDriveAccess(string $value): bool
    {
        return strlen($value) >= 3
            && ctype_alpha($value[0])
            && $value[1] === ':'
            && ($value[2] === '\\' || $value[2] === '/');
    }

    private static function isWindowsDriveReference(string $value): bool
    {
        return strlen($value) >= 3
            && ctype_alpha($value[0])
            && $value[1] === ':'
            && $value[2] === '/';
    }
}
