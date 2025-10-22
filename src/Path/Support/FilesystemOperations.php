<?php

namespace Orryv\Path\Support;

use Orryv\Path\Exceptions\DifferentOriginException;

final class FilesystemOperations
{
    private function __construct()
    {
    }

    public static function isSameOrigin(FilesystemPathData $a, FilesystemPathData $b): bool
    {
        if ($a->isWindowsDrive && $b->isWindowsDrive) {
            return strtoupper(substr($a->root, 0, 2)) === strtoupper(substr($b->root, 0, 2));
        }

        if ($a->isUnc && $b->isUnc) {
            return strcasecmp($a->root, $b->root) === 0;
        }

        if ($a->isPosix && $b->isPosix) {
            return true;
        }

        return false;
    }

    public static function assertSameOrigin(FilesystemPathData $a, FilesystemPathData $b): void
    {
        if (!self::isSameOrigin($a, $b)) {
            throw new DifferentOriginException('Paths do not share the same origin.');
        }
    }

    public static function isWithin(FilesystemPathData $base, FilesystemPathData $target, bool $targetIsDirectory): bool
    {
        if (!self::isSameOrigin($base, $target)) {
            return false;
        }

        $baseSegments = $base->segments;
        $targetSegments = $target->segments;

        if (!$targetIsDirectory && $targetSegments !== []) {
            $targetSegments = array_slice($targetSegments, 0, -1);
        }

        if (count($baseSegments) > count($targetSegments)) {
            return false;
        }

        foreach ($baseSegments as $index => $segment) {
            if (($targetSegments[$index] ?? null) !== $segment) {
                return false;
            }
        }

        return true;
    }

    public static function relativeSegments(
        FilesystemPathData $from,
        bool $fromIsDirectory,
        FilesystemPathData $to,
        bool $toIsDirectory
    ): array {
        self::assertSameOrigin($from, $to);

        $fromSegments = $from->segments;
        $fromFlags = $from->normalizedFlags;
        if (!$fromIsDirectory && $fromSegments !== []) {
            $fromSegments = array_slice($fromSegments, 0, -1);
            $fromFlags = array_slice($fromFlags, 0, -1);
        }

        $toSegments = $to->segments;
        $toFlags = $to->normalizedFlags;

        $length = min(count($fromSegments), count($toSegments));
        $index = 0;
        while ($index < $length && $fromSegments[$index] === $toSegments[$index]) {
            $index++;
        }

        $up = array_fill(0, count($fromSegments) - $index, '..');
        $upFlags = array_fill(0, count($fromSegments) - $index, false);
        $down = array_slice($toSegments, $index);
        $downFlags = array_slice($toFlags, $index);

        return [
            array_merge($up, $down),
            array_merge($upFlags, $downFlags),
        ];
    }

    public static function commonSegments(
        FilesystemPathData $left,
        bool $leftIsDirectory,
        FilesystemPathData $right,
        bool $rightIsDirectory
    ): array {
        self::assertSameOrigin($left, $right);

        $leftSegments = $left->segments;
        $leftFlags = $left->normalizedFlags;
        if (!$leftIsDirectory && $leftSegments !== []) {
            $leftSegments = array_slice($leftSegments, 0, -1);
            $leftFlags = array_slice($leftFlags, 0, -1);
        }

        $rightSegments = $right->segments;
        if (!$rightIsDirectory && $rightSegments !== []) {
            $rightSegments = array_slice($rightSegments, 0, -1);
        }

        $length = min(count($leftSegments), count($rightSegments));
        $common = [];
        $flags = [];

        for ($i = 0; $i < $length; $i++) {
            if ($leftSegments[$i] !== $rightSegments[$i]) {
                break;
            }

            $common[] = $leftSegments[$i];
            $flags[] = $leftFlags[$i] ?? false;
        }

        return [$common, $flags];
    }
}
