<?php

namespace Tests\Integration;

use Orryv\Path;
use Orryv\Path\Enums\PathFormat;
use PHPUnit\Framework\TestCase;

class WindowsLongPathSupportTest extends TestCase
{
    public function testAddsPrefixOnlyWhenEnabledAndOverLimit(): void
    {
        $segments = str_repeat('deep\\', 60);
        $accessPath = 'C:\\' . $segments . 'file.txt';

        $path = Path::file($accessPath, PathFormat::ACCESS_PATH);

        $disabled = $path->withWindowsLongPathSupport(false);
        $this->assertSame($accessPath, $disabled->toString(PathFormat::ACCESS_PATH));

        $enabled = $path->withWindowsLongPathSupport(true);
        $this->assertSame('\\\\?\\' . $accessPath, $enabled->toString(PathFormat::ACCESS_PATH));

        $this->assertSame(
            'C:/'.str_replace('\\', '/', $segments).'file.txt',
            $enabled->toString(PathFormat::REFERENCE_PATH)
        );
    }

    public function testEnablingLongPathSupportKeepsShortPathsUnchanged(): void
    {
        $path = Path::file('C:\\short\\file.txt', PathFormat::ACCESS_PATH);

        $enabled = $path->withWindowsLongPathSupport(true);

        $this->assertSame('C:\\short\\file.txt', $enabled->toString(PathFormat::ACCESS_PATH));
        $this->assertSame('C:/short/file.txt', $enabled->toString(PathFormat::REFERENCE_PATH));
    }

    public function testDoesNotDuplicateExistingLongPathPrefix(): void
    {
        $prefixed = '\\\\?\\C:\\very\\long\\path\\file.txt';
        $path = Path::file($prefixed, PathFormat::ACCESS_PATH);

        $enabled = $path->withWindowsLongPathSupport(true);
        $this->assertSame($prefixed, $enabled->toString(PathFormat::ACCESS_PATH));

        $disabled = $path->withWindowsLongPathSupport(false);
        $this->assertSame('C:\\very\\long\\path\\file.txt', $disabled->toString(PathFormat::ACCESS_PATH));
    }

    public function testUncPathsUseLongPathUNCVariant(): void
    {
        $unc = '\\server\\share\\' . str_repeat('folder\\', 40) . 'file.txt';
        $path = Path::file($unc, PathFormat::ACCESS_PATH)->withWindowsLongPathSupport(true);

        $this->assertSame('\\\\?\\UNC\\server\\share\\' . str_repeat('folder\\', 40) . 'file.txt', $path->toString(PathFormat::ACCESS_PATH));
        $this->assertSame('file://server/share/' . str_repeat('folder/', 40) . 'file.txt', $path->toString(PathFormat::ACCESS_URI));
    }

    public function testToggleLongPathSupportIsIdempotent(): void
    {
        $access = '\\server\\share\\deep\\file.txt';
        $path = Path::file('\\\\?\\UNC\\server\\share\\deep\\file.txt', PathFormat::ACCESS_PATH);

        $disabled = $path->withWindowsLongPathSupport(false);
        $this->assertSame($access, $disabled->toString(PathFormat::ACCESS_PATH));

        $reEnabled = $disabled->withWindowsLongPathSupport(true);
        $this->assertSame('\\\\?\\UNC\\server\\share\\deep\\file.txt', $reEnabled->toString(PathFormat::ACCESS_PATH));
    }
}
