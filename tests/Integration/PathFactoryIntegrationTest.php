<?php

namespace Tests\Integration;

use InvalidArgumentException;
use Orryv\Path;
use Orryv\Path\DirectoryPath;
use Orryv\Path\Enums\PathFormat;
use Orryv\Path\FilePath;
use Orryv\Path\UrlPath;
use PHPUnit\Framework\TestCase;

class PathFactoryIntegrationTest extends TestCase
{
    public function testFileFactoryConvertsBetweenFormats(): void
    {
        $path = Path::file('C:/Projects/App/index.php', PathFormat::REFERENCE_PATH);

        $this->assertInstanceOf(FilePath::class, $path);
        $this->assertSame('C:/Projects/App/index.php', $path->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('C:\\Projects\\App\\index.php', $path->toString(PathFormat::ACCESS_PATH));
        $this->assertSame('file:///C:/Projects/App/index.php', $path->toString(PathFormat::ACCESS_URI));
    }

    public function testDirectoryFactoryPreservesExplicitTrailingSeparator(): void
    {
        $path = Path::dir('//server/share/logs/', PathFormat::REFERENCE_PATH);

        $this->assertInstanceOf(DirectoryPath::class, $path);
        $this->assertTrue($path->preservesEndSlash());
        $this->assertSame('//server/share/logs/', $path->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('\\server\\share\\logs\\', $path->toString(PathFormat::ACCESS_PATH));
        $this->assertSame('file://server/share/logs/', $path->toString(PathFormat::ACCESS_URI));
    }

    public function testUrlFactoryRetainsComponents(): void
    {
        $path = Path::url('https://example.com/a b/c?x=1#frägment', PathFormat::ACCESS_URI);

        $this->assertInstanceOf(UrlPath::class, $path);
        $this->assertSame('https://example.com/a%20b/c?x=1#fr%C3%A4gment', $path->toString(PathFormat::ACCESS_URI));
        $this->assertSame('https://example.com/a b/c?x=1#frägment', $path->toString(PathFormat::REFERENCE_PATH));
    }

    public function testDotFactoryDetectsFileFromReferencePath(): void
    {
        $path = Path::dot('C:/Projects/App/bootstrap.php', PathFormat::REFERENCE_PATH);

        $this->assertInstanceOf(FilePath::class, $path);
        $this->assertSame('C:/Projects/App/bootstrap.php', $path->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('C:\\Projects\\App\\bootstrap.php', $path->toString(PathFormat::ACCESS_PATH));
        $this->assertSame('file:///C:/Projects/App/bootstrap.php', $path->toString(PathFormat::ACCESS_URI));
    }

    public function testDotFactoryDetectsDirectoryWithoutHint(): void
    {
        $path = Path::dot('//server/share/app', PathFormat::REFERENCE_PATH);

        $this->assertInstanceOf(DirectoryPath::class, $path);
        $this->assertFalse($path->preservesEndSlash());
        $this->assertSame('//server/share/app', $path->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('\\server\\share\\app', $path->toString(PathFormat::ACCESS_PATH));
        $this->assertSame('file://server/share/app/', $path->toString(PathFormat::ACCESS_URI));
    }

    public function testDotFactoryDetectsDirectoryFromAccessPath(): void
    {
        $path = Path::dot('C:\\Projects\\Logs', PathFormat::ACCESS_PATH);

        $this->assertInstanceOf(DirectoryPath::class, $path);
        $this->assertFalse($path->preservesEndSlash());
        $this->assertSame('C:\\Projects\\Logs', $path->toString(PathFormat::ACCESS_PATH));
        $this->assertSame('C:/Projects/Logs', $path->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('file:///C:/Projects/Logs', $path->toString(PathFormat::ACCESS_URI));
    }

    public function testDotFactoryHonorsAccessPathTrailingBackslash(): void
    {
        $path = Path::dot('C:\\Projects\\Logs\\', PathFormat::ACCESS_PATH);

        $this->assertInstanceOf(DirectoryPath::class, $path);
        $this->assertTrue($path->preservesEndSlash());
        $this->assertSame('C:\\Projects\\Logs\\', $path->toString(PathFormat::ACCESS_PATH));
        $this->assertSame('C:/Projects/Logs/', $path->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('file:///C:/Projects/Logs/', $path->toString(PathFormat::ACCESS_URI));
    }

    public function testDotFactoryHonorsAccessUriTrailingSlash(): void
    {
        $path = Path::dot('file:///var/www/html/', PathFormat::ACCESS_URI);

        $this->assertInstanceOf(DirectoryPath::class, $path);
        $this->assertTrue($path->preservesEndSlash());
        $this->assertSame('/var/www/html/', $path->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('/var/www/html/', $path->toString(PathFormat::ACCESS_PATH));
        $this->assertSame('file:///var/www/html/', $path->toString(PathFormat::ACCESS_URI));
    }

    public function testDotFactoryDetectsAccessUriFile(): void
    {
        $path = Path::dot('file:///var/www/index.php', PathFormat::ACCESS_URI);

        $this->assertInstanceOf(FilePath::class, $path);
        $this->assertSame('/var/www/index.php', $path->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('/var/www/index.php', $path->toString(PathFormat::ACCESS_PATH));
        $this->assertSame('file:///var/www/index.php', $path->toString(PathFormat::ACCESS_URI));
    }

    public function testSystemFactoryDetectsExistingDirectory(): void
    {
        $base = $this->createFilesystemSandbox();
        $directory = $base . '/Reports & Logs';
        if (!mkdir($directory) && !is_dir($directory)) {
            self::fail('Unable to create sandbox directory.');
        }

        $path = Path::system($directory, PathFormat::ACCESS_PATH);
        $expected = Path::dir($directory, PathFormat::ACCESS_PATH);

        $this->assertInstanceOf(DirectoryPath::class, $path);
        $this->assertSame($directory, $path->toString(PathFormat::ACCESS_PATH));
        $this->assertSame($expected->toString(PathFormat::REFERENCE_PATH), $path->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expected->toString(PathFormat::ACCESS_URI), $path->toString(PathFormat::ACCESS_URI));

        $this->cleanupSandbox([$directory, $base]);
    }

    public function testSystemFactoryDetectsExistingFile(): void
    {
        $base = $this->createFilesystemSandbox();
        $directory = $base . '/storage';
        if (!mkdir($directory) && !is_dir($directory)) {
            self::fail('Unable to create sandbox directory.');
        }

        $file = $directory . '/report.txt';
        file_put_contents($file, 'contents');

        $path = Path::system($file, PathFormat::ACCESS_PATH);
        $expected = Path::file($file, PathFormat::ACCESS_PATH);

        $this->assertInstanceOf(FilePath::class, $path);
        $this->assertSame($file, $path->toString(PathFormat::ACCESS_PATH));
        $this->assertSame($expected->toString(PathFormat::REFERENCE_PATH), $path->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expected->toString(PathFormat::ACCESS_URI), $path->toString(PathFormat::ACCESS_URI));

        $this->cleanupSandbox([$file, $directory, $base]);
    }

    public function testSystemFactoryHandlesReferencePathInput(): void
    {
        $base = $this->createFilesystemSandbox();
        $directory = $base . '/data';
        if (!mkdir($directory) && !is_dir($directory)) {
            self::fail('Unable to create sandbox directory.');
        }

        $file = $directory . '/report.txt';
        file_put_contents($file, 'contents');

        $reference = Path::file($file, PathFormat::ACCESS_PATH)->toString(PathFormat::REFERENCE_PATH);
        $path = Path::system($reference, PathFormat::REFERENCE_PATH);

        $this->assertInstanceOf(FilePath::class, $path);
        $this->assertSame($reference, $path->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($file, $path->toString(PathFormat::ACCESS_PATH));
        $this->assertSame(Path::file($file, PathFormat::ACCESS_PATH)->toString(PathFormat::ACCESS_URI), $path->toString(PathFormat::ACCESS_URI));

        $this->cleanupSandbox([$file, $directory, $base]);
    }

    public function testSystemFactoryHandlesAccessUriInput(): void
    {
        $base = $this->createFilesystemSandbox();
        $directory = $base . '/cache';
        if (!mkdir($directory) && !is_dir($directory)) {
            self::fail('Unable to create sandbox directory.');
        }

        $uri = Path::dir($directory, PathFormat::ACCESS_PATH)->toString(PathFormat::ACCESS_URI);
        $path = Path::system($uri, PathFormat::ACCESS_URI);

        $expected = Path::dir($directory, PathFormat::ACCESS_PATH);

        $this->assertInstanceOf(DirectoryPath::class, $path);
        $this->assertSame($uri, $path->toString(PathFormat::ACCESS_URI));
        $this->assertSame($expected->toString(PathFormat::REFERENCE_PATH), $path->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expected->toString(PathFormat::ACCESS_PATH), $path->toString(PathFormat::ACCESS_PATH));

        $this->cleanupSandbox([$directory, $base]);
    }

    public function testSystemFactoryRespectsTrailingSeparatorHint(): void
    {
        $base = $this->createFilesystemSandbox();
        $directory = $base . '/logs';
        if (!mkdir($directory) && !is_dir($directory)) {
            self::fail('Unable to create sandbox directory.');
        }

        $path = Path::system($directory . '/', PathFormat::ACCESS_PATH);
        $expected = Path::dir($directory . '/', PathFormat::ACCESS_PATH);

        $this->assertInstanceOf(DirectoryPath::class, $path);
        $this->assertTrue($path->preservesEndSlash());
        $this->assertSame(rtrim($directory, '/') . '/', $path->toString(PathFormat::ACCESS_PATH));
        $this->assertSame($expected->toString(PathFormat::REFERENCE_PATH), $path->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expected->toString(PathFormat::ACCESS_URI), $path->toString(PathFormat::ACCESS_URI));

        $this->cleanupSandbox([$directory, $base]);
    }

    public function testSystemFactoryThrowsForMissingPath(): void
    {
        $base = $this->createFilesystemSandbox();
        $missing = $base . '/missing.txt';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path does not exist');

        try {
            Path::system($missing, PathFormat::ACCESS_PATH);
        } finally {
            $this->cleanupSandbox([$base]);
        }
    }

    public function testSystemFactoryRejectsNonFileUri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only file:// URIs');

        Path::system('https://example.com/archive.zip', PathFormat::ACCESS_URI);
    }

    private function createFilesystemSandbox(): string
    {
        $base = sys_get_temp_dir() . '/path-factory-integration-' . uniqid('', true);
        if (!mkdir($base) && !is_dir($base)) {
            self::fail('Unable to create sandbox root.');
        }

        return $base;
    }

    /**
     * @param list<string> $paths
     */
    private function cleanupSandbox(array $paths): void
    {
        foreach ($paths as $path) {
            if ($path === null || $path === '') {
                continue;
            }

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
