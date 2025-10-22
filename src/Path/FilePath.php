<?php

namespace Orryv\Path;

use Orryv\Path\Enums\PathFormat;
use Orryv\Path\Support\FilesystemOperations;
use Orryv\Path\Support\FilesystemParser;
use Orryv\Path\Support\FilesystemPathData;
use Orryv\Path\Support\Unicode;

final class FilePath extends FilesystemPath
{
    private function __construct(FilesystemPathData $data, ?DirectoryPath $baseDir = null)
    {
        parent::__construct($data, $baseDir);
    }

    public static function from(string $value, PathFormat $format = PathFormat::ACCESS_PATH): self
    {
        $data = FilesystemParser::parse($value, $format);

        return new self($data);
    }

    public static function fromData(FilesystemPathData $data, ?DirectoryPath $baseDir = null): self
    {
        return new self($data, $baseDir);
    }

    public function toString(PathFormat $format = PathFormat::ACCESS_PATH): string
    {
        return $this->render($format, false, false);
    }

    protected function isDirectory(): bool
    {
        return false;
    }

    protected function cloneWith(FilesystemPathData $data, ?DirectoryPath $baseDir = null): static
    {
        return new self($data, $baseDir ?? $this->baseDir);
    }

    public function withDirectory(string|DirectoryPath $directory): self
    {
        $dir = $this->normalizeDirectory($directory);

        FilesystemOperations::assertSameOrigin($dir->data, $this->data);

        $segments = [];
        $flags = [];
        foreach ($dir->data->segments as $segment) {
            [$normalizedSegment, $changed] = Unicode::normalizeSegment($segment);
            $segments[] = $normalizedSegment;
            $flags[] = $changed;
        }

        $segments[] = $this->getFilename();
        $flags[] = $this->data->normalizedFlags[count($this->data->segments) - 1] ?? false;

        $data = new FilesystemPathData(
            $dir->data->root,
            $segments,
            $dir->data->isWindowsDrive,
            $dir->data->isUnc,
            $dir->data->isPosix,
            $this->data->longPathPreference,
            $this->data->hadLongPathPrefix,
            $flags,
            $dir->data->uncPrefixLength,
            $dir->data->preferForwardSlash,
            $dir->data->longPathPrefixSlashes,
        );

        return new self($data, $this->baseDir);
    }

    private function getFilename(): string
    {
        return $this->data->segments[count($this->data->segments) - 1] ?? '';
    }
}
