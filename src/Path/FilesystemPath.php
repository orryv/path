<?php

namespace Orryv\Path;

use InvalidArgumentException;
use Orryv\Path\Enums\PathFormat;
use Orryv\Path\Exceptions\DifferentOriginException;
use Orryv\Path\Support\FilesystemOperations;
use Orryv\Path\Support\FilesystemPathData;
use Orryv\Path\Support\FilesystemRenderer;
use Orryv\Path\Support\Unicode;
use OutOfBoundsException;

abstract class FilesystemPath
{
    protected FilesystemPathData $data;

    protected ?DirectoryPath $baseDir;

    protected function __construct(FilesystemPathData $data, ?DirectoryPath $baseDir = null)
    {
        $this->data = $data;
        $this->baseDir = $baseDir;
    }

    abstract protected function isDirectory(): bool;

    abstract protected function cloneWith(FilesystemPathData $data, ?DirectoryPath $baseDir = null): static;

    protected function render(PathFormat $format, bool $isDirectory, bool $preserveTrailingSlash): string
    {
        return match ($format) {
            PathFormat::REFERENCE_PATH => FilesystemRenderer::toReferencePath($this->data, $isDirectory, $preserveTrailingSlash),
            PathFormat::ACCESS_PATH => FilesystemRenderer::toAccessPath($this->data, $isDirectory, $preserveTrailingSlash),
            PathFormat::ACCESS_URI => FilesystemRenderer::toAccessUri($this->data, $isDirectory, $preserveTrailingSlash),
        };
    }

    public function equals(object $other): bool
    {
        return $other instanceof self
            && static::class === $other::class
            && $this->data->root === $other->data->root
            && $this->data->segments === $other->data->segments;
    }

    public function withWindowsLongPathSupport(bool $enabled): static
    {
        if (($this->data->longPathPreference && $enabled) || (!$this->data->longPathPreference && !$enabled)) {
            return $this;
        }

        $data = $this->data->withLongPathPreference($enabled);

        return $this->cloneWith($data, $this->baseDir);
    }

    public function withBaseDir(string|DirectoryPath $base): static
    {
        $baseDir = $this->normalizeDirectory($base);

        if (!FilesystemOperations::isSameOrigin($this->data, $baseDir->data)) {
            throw new DifferentOriginException('Base directory must share the same origin.');
        }

        if (!FilesystemOperations::isWithin($baseDir->data, $this->data, $this->isDirectory())) {
            throw new OutOfBoundsException('The current path is outside the provided base directory.');
        }

        return $this->cloneWith($this->data, $baseDir);
    }

    public function cd(string $relative): FilesystemPath
    {
        $relative = trim($relative);

        if ($relative === '') {
            if ($this instanceof FilePath) {
                [$segments, $flags] = $this->currentDirectoryState();

                return DirectoryPath::fromData($this->data->withSegments($segments, $flags), true, $this->baseDir);
            }

            return $this;
        }

        $normalized = str_replace('\\', '/', $relative);
        $explicitDirectory = self::isExplicitDirectoryHint($normalized);
        $isAbsolute = str_starts_with($normalized, '/');

        $parts = array_values(array_filter(explode('/', $normalized), static fn ($part) => $part !== ''));

        $baseData = $this->baseDir?->data ?? $this->data;
        $root = $baseData->root;
        if ($isAbsolute) {
            if ($this->baseDir !== null) {
                $segments = $this->baseDir->data->segments;
                $flags = $this->baseDir->data->normalizedFlags;
            } else {
                $segments = [];
                $flags = [];
            }
        } else {
            if ($this instanceof FilePath && in_array('..', $parts, true)) {
                $segments = $this->data->segments;
                $flags = $this->data->normalizedFlags;
            } else {
                [$segments, $flags] = $this->currentDirectoryState();
            }
        }

        foreach ($parts as $part) {
            if ($part === '.') {
                continue;
            }

            if ($part === '..') {
                if ($segments === []) {
                    throw new OutOfBoundsException('Cannot navigate above the root.');
                }
                array_pop($segments);
                array_pop($flags);
                continue;
            }

            [$normalizedPart, $changed] = Unicode::normalizeSegment($part);
            $segments[] = $normalizedPart;
            $flags[] = $changed;
        }

        $uncPrefixLength = $baseData->isUnc ? $baseData->uncPrefixLength : 0;
        if ($this->baseDir !== null && $baseData->isUnc) {
            $uncPrefixLength = max(2, $uncPrefixLength);
        }

        $preferForwardSlash = $baseData->preferForwardSlash;
        if ($this->baseDir !== null && $baseData->isWindowsDrive) {
            $preferForwardSlash = true;
        }

        $targetData = new FilesystemPathData(
            $root,
            array_values($segments),
            $baseData->isWindowsDrive,
            $baseData->isUnc,
            $baseData->isPosix,
            $this->data->longPathPreference,
            $this->data->hadLongPathPrefix,
            array_values($flags),
            $uncPrefixLength,
            $preferForwardSlash,
            $baseData->longPathPrefixSlashes,
        );

        $isDirectory = $explicitDirectory;
        if (!$isDirectory) {
            $isDirectory = $this instanceof DirectoryPath;
        }
        if (!$isDirectory && $normalized === '..') {
            $isDirectory = true;
        }

        if ($this->baseDir !== null && !FilesystemOperations::isWithin($this->baseDir->data, $targetData, $isDirectory)) {
            throw new OutOfBoundsException('Navigating outside of base directory is not allowed.');
        }

        if ($isDirectory) {
            $preserve = $this->baseDir?->preservesEndSlash() ?? true;

            return DirectoryPath::fromData($targetData, $preserve, $this->baseDir);
        }

        return FilePath::fromData($targetData, $this->baseDir);
    }

    public function getRelativePathFrom(string|FilesystemPath $base, PathFormat $format = PathFormat::REFERENCE_PATH): string
    {
        $basePath = $this->normalizePath($base);

        FilesystemOperations::assertSameOrigin($basePath->data, $this->data);

        [$segments, $flags] = FilesystemOperations::relativeSegments(
            $basePath->data,
            $basePath->isDirectory(),
            $this->data,
            $this->isDirectory()
        );

        return $this->formatSegments($segments, $flags, $format, $basePath->data);
    }

    public function getRelativePathTo(string|FilesystemPath $target, PathFormat $format = PathFormat::REFERENCE_PATH): string
    {
        $targetPath = $this->normalizePath($target);

        return $targetPath->getRelativePathFrom($this, $format);
    }

    public function getCommonBasePath(string|FilesystemPath $other, PathFormat $format): DirectoryPath
    {
        $otherPath = $this->normalizePath($other);

        FilesystemOperations::assertSameOrigin($otherPath->data, $this->data);

        [$segments, $flags] = FilesystemOperations::commonSegments($this->data, $this->isDirectory(), $otherPath->data, $otherPath->isDirectory());

        $uncPrefixLength = $this->data->isUnc ? $this->data->uncPrefixLength : 0;
        $preferForwardSlash = $this->data->preferForwardSlash;

        $data = new FilesystemPathData(
            $this->data->root,
            $segments,
            $this->data->isWindowsDrive,
            $this->data->isUnc,
            $this->data->isPosix,
            $this->data->longPathPreference,
            $this->data->hadLongPathPrefix,
            $flags,
            $uncPrefixLength,
            $preferForwardSlash,
            $this->data->longPathPrefixSlashes,
        );

        $directory = DirectoryPath::fromData($data, true, $this->baseDir);

        return $format === PathFormat::ACCESS_PATH
            ? $directory->withPreserveEndSlash(true)
            : $directory;
    }

    protected function formatSegments(array $segments, array $flags, PathFormat $format, FilesystemPathData $context): string
    {
        if ($segments === []) {
            return '';
        }

        $separator = match ($format) {
            PathFormat::ACCESS_PATH => $context->isUnc ? '\\' : '/',
            default => '/',
        };

        $converted = [];
        foreach ($segments as $index => $segment) {
            $converted[] = Unicode::escapeSegment($segment, $flags[$index] ?? false);
        }

        return implode($separator, $converted);
    }

    protected function normalizeDirectory(string|DirectoryPath $directory): DirectoryPath
    {
        if ($directory instanceof DirectoryPath) {
            return $directory;
        }

        return DirectoryPath::from($directory, PathFormat::REFERENCE_PATH);
    }

    protected function normalizePath(string|FilesystemPath $path): FilesystemPath
    {
        if ($path instanceof FilesystemPath) {
            return $path;
        }

        $trimmed = trim($path);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Path cannot be empty.');
        }

        if (str_ends_with($trimmed, '/') || str_ends_with($trimmed, '\\')) {
            return DirectoryPath::from($trimmed, PathFormat::REFERENCE_PATH);
        }

        if (str_contains($trimmed, '.')) {
            return FilePath::from($path, PathFormat::REFERENCE_PATH);
        }

        return DirectoryPath::from($trimmed, PathFormat::REFERENCE_PATH);
    }

    /**
     * @return list<string>
     */
    protected function currentDirectorySegments(): array
    {
        return $this->currentDirectoryState()[0];
    }

    /**
     * @return array{0:list<string>,1:list<bool>}
     */
    protected function currentDirectoryState(): array
    {
        if ($this->isDirectory()) {
            return [$this->data->segments, $this->data->normalizedFlags];
        }

        if ($this->data->segments === []) {
            return [[], []];
        }

        return [
            array_slice($this->data->segments, 0, -1),
            array_slice($this->data->normalizedFlags, 0, -1),
        ];
    }

    private static function isExplicitDirectoryHint(string $normalized): bool
    {
        $trimmed = rtrim($normalized);

        return $trimmed === ''
            || str_ends_with($normalized, '/')
            || $trimmed === '.'
            || $trimmed === '..';
    }

}
