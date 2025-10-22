<?php

namespace Orryv\Path;

use Orryv\Path\Enums\PathFormat;
use Orryv\Path\Support\FilesystemParser;
use Orryv\Path\Support\FilesystemPathData;

final class DirectoryPath extends FilesystemPath
{
    private bool $preserveTrailingSlash;

    private function __construct(FilesystemPathData $data, bool $preserveTrailingSlash = true, ?DirectoryPath $baseDir = null)
    {
        parent::__construct($data, $baseDir);
        $this->preserveTrailingSlash = $preserveTrailingSlash;
    }

    public static function from(string $value, PathFormat $format = PathFormat::ACCESS_PATH): self
    {
        $data = FilesystemParser::parse($value, $format);
        $preserve = self::detectPreserveFlag($value);

        return new self($data, $preserve);
    }

    public static function fromData(FilesystemPathData $data, bool $preserveTrailingSlash = true, ?DirectoryPath $baseDir = null): self
    {
        return new self($data, $preserveTrailingSlash, $baseDir);
    }

    public function toString(PathFormat $format = PathFormat::ACCESS_PATH): string
    {
        return $this->render($format, true, $this->preserveTrailingSlash);
    }

    public function withPreserveEndSlash(bool $preserve): self
    {
        if ($this->preserveTrailingSlash === $preserve) {
            return $this;
        }

        return new self($this->data, $preserve, $this->baseDir);
    }

    public function preservesEndSlash(): bool
    {
        return $this->preserveTrailingSlash;
    }

    protected function isDirectory(): bool
    {
        return true;
    }

    protected function cloneWith(FilesystemPathData $data, ?DirectoryPath $baseDir = null): static
    {
        return new self($data, $this->preserveTrailingSlash, $baseDir ?? $this->baseDir);
    }

    private static function detectPreserveFlag(string $value): bool
    {
        $value = trim($value);

        return str_ends_with($value, '/') || str_ends_with($value, '\\');
    }
}
