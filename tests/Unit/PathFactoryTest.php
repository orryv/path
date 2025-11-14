<?php

namespace Tests\Unit;

use InvalidArgumentException;
use Orryv\Path;
use Orryv\Path\DirectoryPath;
use Orryv\Path\Enums\PathFormat;
use Orryv\Path\FilePath;
use PHPUnit\Framework\TestCase;

class PathFactoryTest extends TestCase
{
    public function testDotDetectsFileWhenExtensionPresent(): void
    {
        $path = Path::dot('C:/Projects/demo/app.php', PathFormat::REFERENCE_PATH);

        $this->assertInstanceOf(FilePath::class, $path);
        $this->assertSame('C:/Projects/demo/app.php', $path->toString(PathFormat::REFERENCE_PATH));
    }

    public function testDotDefaultsToDirectoryWhenNoExtension(): void
    {
        $path = Path::dot('C:/Projects/demo', PathFormat::REFERENCE_PATH);

        $this->assertInstanceOf(DirectoryPath::class, $path);
        $this->assertFalse($path->preservesEndSlash());
        $this->assertSame('C:/Projects/demo', $path->toString(PathFormat::REFERENCE_PATH));
    }

    public function testDotPreservesExplicitTrailingSeparator(): void
    {
        $path = Path::dot('C:/Projects/demo/', PathFormat::REFERENCE_PATH);

        $this->assertInstanceOf(DirectoryPath::class, $path);
        $this->assertTrue($path->preservesEndSlash());
        $this->assertSame('C:/Projects/demo/', $path->toString(PathFormat::REFERENCE_PATH));
    }

    public function testSystemDetectsExistingDirectory(): void
    {
        $base = sys_get_temp_dir() . '/path-factory-' . uniqid();
        $directory = $base . '/storage';

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            self::fail('Unable to create temporary directory for the test.');
        }

        $path = Path::system($directory, PathFormat::ACCESS_PATH);

        $this->assertInstanceOf(DirectoryPath::class, $path);
        $this->assertSame($directory, $path->toString(PathFormat::ACCESS_PATH));

        $this->cleanupFilesystem([$directory, $base]);
    }

    public function testSystemDetectsExistingFile(): void
    {
        $base = sys_get_temp_dir() . '/path-factory-' . uniqid();
        $file = $base . '/report.txt';

        if (!is_dir($base) && !mkdir($base, 0777, true) && !is_dir($base)) {
            self::fail('Unable to create temporary directory for the test.');
        }

        file_put_contents($file, 'content');

        $path = Path::system($file, PathFormat::ACCESS_PATH);

        $this->assertInstanceOf(FilePath::class, $path);
        $this->assertSame($file, $path->toString(PathFormat::ACCESS_PATH));

        $this->cleanupFilesystem([$file, $base]);
    }

    public function testSystemThrowsWhenPathDoesNotExist(): void
    {
        $base = sys_get_temp_dir() . '/path-factory-' . uniqid();
        $missing = $base . '/missing.txt';

        $this->expectException(InvalidArgumentException::class);
        Path::system($missing, PathFormat::ACCESS_PATH);
    }

    private function cleanupFilesystem(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
                continue;
            }

            if (is_dir($path)) {
                @rmdir($path);
            }
        }
    }
}
