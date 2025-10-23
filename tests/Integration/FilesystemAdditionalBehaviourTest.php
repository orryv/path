<?php

namespace Tests\Integration;

use Orryv\Path;
use Orryv\Path\DirectoryPath;
use Orryv\Path\Enums\PathFormat;
use Orryv\Path\FilePath;
use Orryv\Path\FilesystemPath;
use PHPUnit\Framework\TestCase;

class FilesystemAdditionalBehaviourTest extends TestCase
{
    /**
     * @dataProvider preserveSlashProvider
     */
    public function testWithPreserveEndSlashAdjustsOutput(callable $factory, bool $preserve, string $expectedReference, string $expectedAccess): void
    {
        $directory = $factory();

        $updated = $directory->withPreserveEndSlash($preserve);

        $this->assertSame($preserve, $updated->preservesEndSlash());
        $this->assertSame($expectedReference, $updated->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expectedAccess, $updated->toString(PathFormat::ACCESS_PATH));
    }

    public static function preserveSlashProvider(): iterable
    {
        yield 'windows disable trailing slash' => [
            static fn () => Path::dir('C:/Projects/App/', PathFormat::REFERENCE_PATH),
            false,
            'C:/Projects/App',
            'C:\\Projects\\App',
        ];

        yield 'windows re-enable trailing slash' => [
            static fn () => Path::dir('C:/Projects/App/', PathFormat::REFERENCE_PATH)->withPreserveEndSlash(false),
            true,
            'C:/Projects/App/',
            'C:\\Projects\\App\\',
        ];

        $slash = chr(92);
        $uncWithoutTrailing = $slash . $slash . '?' . $slash . 'UNC' . $slash . 'server' . $slash . 'share' . $slash . 'folder';

        yield 'unc disable trailing slash with long prefix' => [
            static fn () => Path::dir('\\\\?\\UNC\\server\\share\\folder\\', PathFormat::ACCESS_PATH),
            false,
            '//server/share/folder',
            $uncWithoutTrailing,
        ];

        yield 'unc re-enable trailing slash with long prefix' => [
            static fn () => Path::dir('\\\\?\\UNC\\server\\share\\folder\\', PathFormat::ACCESS_PATH)->withPreserveEndSlash(false),
            true,
            '//server/share/folder/',
            $uncWithoutTrailing . $slash,
        ];

        yield 'posix disable trailing slash' => [
            static fn () => Path::dir('/var/www/html/', PathFormat::REFERENCE_PATH),
            false,
            '/var/www/html',
            '/var/www/html',
        ];

        yield 'posix re-enable trailing slash' => [
            static fn () => Path::dir('/var/www/html/', PathFormat::REFERENCE_PATH)->withPreserveEndSlash(false),
            true,
            '/var/www/html/',
            '/var/www/html/',
        ];

        yield 'file parent directory disable trailing slash' => [
            static fn () => Path::file('/srv/www/index.php', PathFormat::REFERENCE_PATH)->cd(''),
            false,
            '/srv/www',
            '/srv/www',
        ];

        $uncDerivedAccess = $slash . $slash . 'server' . $slash . 'share' . $slash . 'reports' . $slash;

        yield 'unc derived directory re-enable trailing slash' => [
            static function () {
                $base = Path::dir('\\\\server\\share\\logs\\', PathFormat::ACCESS_PATH);

                return $base->cd('../reports/')->withPreserveEndSlash(false);
            },
            true,
            '//server/share/reports/',
            $uncDerivedAccess,
        ];
    }

    public function testWithPreserveEndSlashReturnsSameInstanceWhenNoChange(): void
    {
        $directory = Path::dir('/var/www/html/', PathFormat::REFERENCE_PATH);

        $this->assertSame($directory, $directory->withPreserveEndSlash(true));
    }

    /**
     * @dataProvider windowsLongPathProvider
     */
    public function testWithWindowsLongPathSupportAdjustsOutput(callable $factory, bool $enabled, string $expectedReference, string $expectedAccess): void
    {
        $path = $factory();

        $updated = $path->withWindowsLongPathSupport($enabled);

        $this->assertSame($expectedReference, $updated->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expectedAccess, $updated->toString(PathFormat::ACCESS_PATH));
    }

    public static function windowsLongPathProvider(): iterable
    {
        $slash = chr(92);
        $windowsSegments = array_fill(0, 30, 'verylongsegment');
        $windowsReference = 'C:/' . implode('/', $windowsSegments) . '/file.txt';
        $windowsAccessWithPrefix = $slash . $slash . '?' . $slash . 'C:' . $slash . implode($slash, $windowsSegments) . $slash . 'file.txt';

        yield 'long windows path gains prefix when enabled' => [
            static fn () => Path::file($windowsReference, PathFormat::REFERENCE_PATH),
            true,
            $windowsReference,
            $windowsAccessWithPrefix,
        ];

        yield 'existing windows prefix removed when disabled' => [
            static fn () => Path::file('\\\\?\\C:\\Projects\\data\\file.txt', PathFormat::ACCESS_PATH),
            false,
            'C:/Projects/data/file.txt',
            'C:\\Projects\\data\\file.txt',
        ];

        yield 'short windows path stays without prefix when enabled' => [
            static fn () => Path::file('C:/Projects/data/file.txt', PathFormat::REFERENCE_PATH),
            true,
            'C:/Projects/data/file.txt',
            'C:\\Projects\\data\\file.txt',
        ];

        $uncSegments = array_fill(0, 40, 'section');
        $uncReference = '//server/share/' . implode('/', $uncSegments) . '/';
        $uncAccessWithPrefix = $slash . $slash . '?' . $slash . 'UNC' . $slash . 'server' . $slash . 'share' . $slash . implode($slash, $uncSegments) . $slash;

        yield 'long unc directory adds UNC prefix when enabled' => [
            static function () use ($uncSegments) {
                $uncPath = '\\\\server\\share\\' . implode('\\', $uncSegments) . '\\';

                return Path::dir($uncPath, PathFormat::ACCESS_PATH);
            },
            true,
            $uncReference,
            $uncAccessWithPrefix,
        ];

        yield 'existing unc prefix removed when disabled' => [
            static fn () => Path::dir('\\\\?\\UNC\\server\\share\\deep\\deep\\', PathFormat::ACCESS_PATH),
            false,
            '//server/share/deep/deep/',
            $slash . 'server' . $slash . 'share' . $slash . 'deep' . $slash . 'deep' . $slash,
        ];

        yield 'short unc directory stays without prefix when enabled' => [
            static fn () => Path::dir('\\\\server\\share\\docs\\', PathFormat::ACCESS_PATH),
            true,
            '//server/share/docs/',
            $slash . $slash . 'server' . $slash . 'share' . $slash . 'docs' . $slash,
        ];

        yield 'posix directory unaffected when enabling support' => [
            static fn () => Path::dir('/var/www/html/', PathFormat::REFERENCE_PATH),
            true,
            '/var/www/html/',
            '/var/www/html/',
        ];

        yield 'posix directory unaffected when disabling support' => [
            static fn () => Path::dir('/var/www/html/', PathFormat::REFERENCE_PATH),
            false,
            '/var/www/html/',
            '/var/www/html/',
        ];

        yield 'short windows path unchanged when disabling support' => [
            static fn () => Path::file('C:/Projects/data/file.txt', PathFormat::REFERENCE_PATH),
            false,
            'C:/Projects/data/file.txt',
            'C:\\Projects\\data\\file.txt',
        ];
    }

    public function testWithWindowsLongPathSupportReturnsSameInstanceWhenStateMatches(): void
    {
        $short = Path::file('C:/Projects/data/file.txt', PathFormat::REFERENCE_PATH);
        $this->assertSame($short, $short->withWindowsLongPathSupport(false));

        $prefixed = Path::file('\\\\?\\C:\\Projects\\data\\file.txt', PathFormat::ACCESS_PATH);
        $this->assertSame($prefixed, $prefixed->withWindowsLongPathSupport(true));
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEqualsMatchesExpected(callable $firstFactory, callable $secondFactory, bool $expected): void
    {
        $first = $firstFactory();
        $second = $secondFactory();

        $this->assertSame($expected, $first->equals($second));

        if ($second instanceof FilesystemPath) {
            $this->assertSame($expected, $second->equals($first));
        } else {
            $this->assertFalse($expected);
        }
    }

    public static function equalsProvider(): iterable
    {
        yield 'file equality across formats' => [
            static fn () => Path::file('C:/Projects/App/index.php', PathFormat::REFERENCE_PATH),
            static fn () => Path::file('C:\\Projects\\App\\index.php', PathFormat::ACCESS_PATH),
            true,
        ];

        yield 'directory equality across formats' => [
            static fn () => Path::dir('//server/share/data/', PathFormat::REFERENCE_PATH),
            static fn () => Path::dir('\\\\server\\share\\data\\', PathFormat::ACCESS_PATH),
            true,
        ];

        yield 'directory equality ignores preserve flag' => [
            static fn () => Path::dir('/var/www/html/', PathFormat::REFERENCE_PATH)->withPreserveEndSlash(false),
            static fn () => Path::dir('/var/www/html/', PathFormat::REFERENCE_PATH),
            true,
        ];

        yield 'directory equality ignores base directory' => [
            static function () {
                $base = Path::dir('C:/Projects/App/', PathFormat::REFERENCE_PATH);

                return Path::dir('C:/Projects/App/config/', PathFormat::REFERENCE_PATH)->withBaseDir($base);
            },
            static fn () => Path::dir('C:/Projects/App/config/', PathFormat::REFERENCE_PATH),
            true,
        ];

        yield 'different filenames are not equal' => [
            static fn () => Path::file('C:/Projects/App/index.php', PathFormat::REFERENCE_PATH),
            static fn () => Path::file('C:/Projects/App/config.php', PathFormat::REFERENCE_PATH),
            false,
        ];

        yield 'file and directory are never equal' => [
            static fn () => Path::file('/var/www/html/index.php', PathFormat::REFERENCE_PATH),
            static fn () => Path::dir('/var/www/html/', PathFormat::REFERENCE_PATH),
            false,
        ];

        yield 'different origins are not equal' => [
            static fn () => Path::dir('//server/share/', PathFormat::REFERENCE_PATH),
            static fn () => Path::dir('C:/', PathFormat::REFERENCE_PATH),
            false,
        ];

        yield 'non filesystem objects are never equal' => [
            static fn () => Path::dir('/var/www/html/', PathFormat::REFERENCE_PATH),
            static fn () => new \stdClass(),
            false,
        ];
    }
}
