<?php

namespace Orryv\Path\Support;

use InvalidArgumentException;

final class FilesystemPathData
{
    /**
     * @param list<string> $segments
     * @param list<bool>   $normalizedFlags
     */
    public function __construct(
        public readonly string $root,
        public readonly array $segments,
        public readonly bool $isWindowsDrive,
        public readonly bool $isUnc,
        public readonly bool $isPosix,
        public readonly bool $longPathPreference,
        public readonly bool $hadLongPathPrefix,
        public readonly array $normalizedFlags,
        public readonly int $uncPrefixLength = 0,
        public readonly bool $preferForwardSlash = false,
        public readonly int $longPathPrefixSlashes = 0,
    ) {
        if (count($segments) !== count($normalizedFlags)) {
            throw new InvalidArgumentException('Normalized flags must match segment count.');
        }

        if ($this->isUnc && $this->uncPrefixLength < 1) {
            throw new InvalidArgumentException('UNC paths must record a prefix length of at least 1.');
        }

        if (!$this->isUnc && $this->uncPrefixLength !== 0) {
            throw new InvalidArgumentException('Non-UNC paths cannot specify a UNC prefix length.');
        }

        if ($this->longPathPrefixSlashes < 0) {
            throw new InvalidArgumentException('Long path prefix slash count cannot be negative.');
        }
    }

    /**
     * @param list<string> $segments
     * @param list<bool>|null $normalizedFlags
     */
    public function withSegments(array $segments, ?array $normalizedFlags = null): self
    {
        $normalizedFlags ??= array_fill(0, count($segments), false);

        return new self(
            $this->root,
            $segments,
            $this->isWindowsDrive,
            $this->isUnc,
            $this->isPosix,
            $this->longPathPreference,
            $this->hadLongPathPrefix,
            $normalizedFlags,
            $this->uncPrefixLength,
            $this->preferForwardSlash,
            $this->longPathPrefixSlashes,
        );
    }

    public function withLongPathPreference(bool $preference): self
    {
        return new self(
            $this->root,
            $this->segments,
            $this->isWindowsDrive,
            $this->isUnc,
            $this->isPosix,
            $preference,
            $this->hadLongPathPrefix,
            $this->normalizedFlags,
            $this->uncPrefixLength,
            $this->preferForwardSlash,
            $this->longPathPrefixSlashes,
        );
    }

    public function withHadLongPathPrefix(bool $hadPrefix): self
    {
        return new self(
            $this->root,
            $this->segments,
            $this->isWindowsDrive,
            $this->isUnc,
            $this->isPosix,
            $this->longPathPreference,
            $hadPrefix,
            $this->normalizedFlags,
            $this->uncPrefixLength,
            $this->preferForwardSlash,
            $this->longPathPrefixSlashes,
        );
    }
}
