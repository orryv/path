<?php

namespace Tests\Integration;

use Orryv\Path;
use Orryv\Path\DirectoryPath;
use Orryv\Path\Enums\PathFormat;
use Orryv\Path\Exceptions\DifferentOriginException;
use Orryv\Path\FilePath;
use PHPUnit\Framework\TestCase;

class FilesystemRelativePathIntegrationTest extends TestCase
{
    /**
     * @dataProvider relativeFromProvider
     */
    public function testGetRelativePathFromProducesExpectedSegments(callable $factory, string|DirectoryPath|FilePath $base, PathFormat $format, string $expected): void
    {
        $path = $factory();

        $this->assertSame($expected, $path->getRelativePathFrom($base, $format));
    }

    public static function relativeFromProvider(): iterable
    {
        yield 'windows file from project root directory' => [
            static fn () => Path::file('C:/Projects/app/config/app.php', PathFormat::REFERENCE_PATH),
            'C:/Projects/app/',
            PathFormat::REFERENCE_PATH,
            'config/app.php',
        ];
        yield 'windows file from nested storage directory' => [
            static fn () => Path::file('C:/Projects/app/config/app.php', PathFormat::REFERENCE_PATH),
            'C:/Projects/app/storage/logs/',
            PathFormat::REFERENCE_PATH,
            '../../config/app.php',
        ];
        yield 'unc directory using access separators' => [
            static fn () => Path::dir('//server/share/app/releases/2024/', PathFormat::REFERENCE_PATH),
            '//server/share/app/',
            PathFormat::ACCESS_PATH,
            'releases\\2024',
        ];
        yield 'posix sibling file path' => [
            static fn () => Path::file('/var/www/html/index.php', PathFormat::REFERENCE_PATH),
            Path::file('/var/www/html/css/app.css', PathFormat::REFERENCE_PATH),
            PathFormat::REFERENCE_PATH,
            '../index.php',
        ];
        yield 'unc file from nested base directory' => [
            static fn () => Path::file('//server/share/app/tools/run.exe', PathFormat::REFERENCE_PATH),
            '//server/share/app/releases/2024/',
            PathFormat::REFERENCE_PATH,
            '../../tools/run.exe',
        ];
        yield 'access uri format keeps logical separators' => [
            static fn () => Path::file('/var/www/html/assets/img/logo.svg', PathFormat::REFERENCE_PATH),
            Path::dir('/var/www/html/', PathFormat::REFERENCE_PATH),
            PathFormat::ACCESS_URI,
            'assets/img/logo.svg',
        ];
        yield 'unicode segments are preserved' => [
            static fn () => Path::file('C:/Projects/app/Ångström/config.ini', PathFormat::REFERENCE_PATH),
            'C:/Projects/app/',
            PathFormat::REFERENCE_PATH,
            'Ångström/config.ini',
        ];
    }

    public function testGetRelativePathFromThrowsOnDifferentOrigins(): void
    {
        $path = Path::file('C:/Projects/app/index.php', PathFormat::REFERENCE_PATH);

        $this->expectException(DifferentOriginException::class);
        $path->getRelativePathFrom('//server/share/app/', PathFormat::REFERENCE_PATH);
    }

    /**
     * @dataProvider relativePairProvider
     */
    public function testGetRelativePathToMatchesInverse(callable $baseFactory, callable $targetFactory, string $expected): void
    {
        $base = $baseFactory();
        $target = $targetFactory();

        $this->assertSame($expected, $base->getRelativePathTo($target, PathFormat::REFERENCE_PATH));
        $this->assertSame($expected, $target->getRelativePathFrom($base, PathFormat::REFERENCE_PATH));
    }

    public static function relativePairProvider(): iterable
    {
        yield 'windows directory to file' => [
            static fn () => Path::dir('C:/Projects/app/', PathFormat::REFERENCE_PATH),
            static fn () => Path::file('C:/Projects/app/config/app.php', PathFormat::REFERENCE_PATH),
            'config/app.php',
        ];
        yield 'unc release directory to tool executable' => [
            static fn () => Path::dir('//server/share/app/releases/2023/', PathFormat::REFERENCE_PATH),
            static fn () => Path::file('//server/share/app/tools/run.exe', PathFormat::REFERENCE_PATH),
            '../../tools/run.exe',
        ];
        yield 'posix file to document root' => [
            static fn () => Path::file('/var/www/html/css/app.css', PathFormat::REFERENCE_PATH),
            static fn () => Path::file('/var/www/html/index.php', PathFormat::REFERENCE_PATH),
            '../index.php',
        ];
    }

    /**
     * @dataProvider commonBaseProvider
     */
    public function testGetCommonBasePathReturnsExpectedDirectories(callable $firstFactory, callable $secondFactory, PathFormat $format, string $expectedReference, string $expectedAccess): void
    {
        $first = $firstFactory();
        $second = $secondFactory();

        $common = $first->getCommonBasePath($second, $format);

        $this->assertInstanceOf(DirectoryPath::class, $common);
        $this->assertSame($expectedReference, $common->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expectedAccess, $common->toString(PathFormat::ACCESS_PATH));
    }

    public static function commonBaseProvider(): iterable
    {
        yield 'windows files share project directory' => [
            static fn () => Path::file('C:/Projects/app/storage/logs/error.log', PathFormat::REFERENCE_PATH),
            static fn () => Path::file('C:/Projects/app/config/app.php', PathFormat::REFERENCE_PATH),
            PathFormat::REFERENCE_PATH,
            'C:/Projects/app/',
            'C:\\Projects\\app\\',
        ];
        yield 'unc directories share releases folder' => [
            static fn () => Path::dir('//server/share/app/releases/2024/', PathFormat::REFERENCE_PATH),
            static fn () => Path::dir('//server/share/app/releases/2023/', PathFormat::REFERENCE_PATH),
            PathFormat::ACCESS_PATH,
            '//server/share/app/releases/',
            '\\server\\share\\app\\releases\\',
        ];
        yield 'posix files share /var/www' => [
            static fn () => Path::file('/var/www/html/index.php', PathFormat::REFERENCE_PATH),
            static fn () => Path::file('/var/www/assets/app.js', PathFormat::REFERENCE_PATH),
            PathFormat::REFERENCE_PATH,
            '/var/www/',
            '/var/www/',
        ];
    }

    public function testGetCommonBasePathRejectsDifferentOrigin(): void
    {
        $first = Path::file('C:/Projects/app/index.php', PathFormat::REFERENCE_PATH);
        $second = Path::file('//server/share/app/index.php', PathFormat::REFERENCE_PATH);

        $this->expectException(DifferentOriginException::class);
        $first->getCommonBasePath($second, PathFormat::REFERENCE_PATH);
    }
}
