<?php

namespace Tests\Integration;

use Orryv\Path;
use Orryv\Path\DirectoryPath;
use Orryv\Path\Enums\PathFormat;
use Orryv\Path\Exceptions\DifferentOriginException;
use Orryv\Path\FilePath;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

class FilesystemNavigationExtendedTest extends TestCase
{
    /**
     * @dataProvider fileCdProvider
     */
    public function testFileCdResolvesRelativeInputs(string $relative, string $expectedReference, string $expectedAccess, string $expectedClass): void
    {
        $file = Path::file('C:/Projects/app/index.php', PathFormat::REFERENCE_PATH);

        $result = $file->cd($relative);

        $this->assertInstanceOf($expectedClass, $result);
        $this->assertSame($expectedReference, $result->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expectedAccess, $result->toString(PathFormat::ACCESS_PATH));
    }

    public static function fileCdProvider(): iterable
    {
        yield 'parent directory' => ['..', 'C:/Projects/app/', 'C:\\Projects\\app\\', DirectoryPath::class];
        yield 'current directory as explicit directory' => ['./', 'C:/Projects/app/', 'C:\\Projects\\app\\', DirectoryPath::class];
        yield 'nested file inside current directory' => ['config/app.php', 'C:/Projects/app/config/app.php', 'C:\\Projects\\app\\config\\app.php', FilePath::class];
        yield 'sibling directory with explicit trailing slash' => ['../logs/', 'C:/Projects/app/logs/', 'C:\\Projects\\app\\logs\\', DirectoryPath::class];
        yield 'ascend twice before descending' => ['../../shared/bootstrap.php', 'C:/Projects/shared/bootstrap.php', 'C:\\Projects\\shared\\bootstrap.php', FilePath::class];
        yield 'mixed separators are normalised' => ['..\\cache\\', 'C:/Projects/app/cache/', 'C:\\Projects\\app\\cache\\', DirectoryPath::class];
        yield 'absolute navigation stays on drive root' => ['/', 'C:/', 'C:\\', DirectoryPath::class];
    }

    /**
     * @dataProvider directoryCdProvider
     */
    public function testDirectoryCdResolvesRelativeInputs(string $relative, string $expectedReference, string $expectedAccess): void
    {
        $directory = Path::dir('//server/share/team/reports/2023/', PathFormat::REFERENCE_PATH);

        $result = $directory->cd($relative);

        $this->assertInstanceOf(DirectoryPath::class, $result);
        $this->assertSame($expectedReference, $result->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expectedAccess, $result->toString(PathFormat::ACCESS_PATH));
    }

    public static function directoryCdProvider(): iterable
    {
        yield 'step up one level' => ['..', '//server/share/team/reports/', '\\server\\share\\team\\reports\\'];
        yield 'step up two levels' => ['../..', '//server/share/team/', '\\server\\share\\team\\'];
        yield 'switch to sibling year directory' => ['../2022/', '//server/share/team/reports/2022/', '\\server\\share\\team\\reports\\2022\\'];
        yield 'treat dotted name as directory' => ['../../archive.zip', '//server/share/team/archive.zip/', '\\server\\share\\team\\archive.zip\\'];
        yield 'jump to share root' => ['/', '//server/share/', '\\server\\share\\'];
    }

    /**
     * @dataProvider cdOutOfBoundsProvider
     */
    public function testCdThrowsWhenNavigatingAboveRoot(callable $factory, string $relative): void
    {
        $path = $factory();

        $this->expectException(OutOfBoundsException::class);
        $path->cd($relative);
    }

    public static function cdOutOfBoundsProvider(): iterable
    {
        yield 'file path cannot ascend above drive root' => [
            static fn () => Path::file('C:/Projects/app/index.php', PathFormat::REFERENCE_PATH),
            '../../../..',
        ];
        yield 'unc directory cannot go above share root' => [
            static fn () => Path::dir('//server/share/', PathFormat::REFERENCE_PATH),
            '..',
        ];
    }

    /**
     * @dataProvider cdBaseDirViolationProvider
     */
    public function testCdWithBaseDirPreventsEscape(callable $factory, string $relative): void
    {
        $path = $factory();

        $this->expectException(OutOfBoundsException::class);
        $path->cd($relative);
    }

    public static function cdBaseDirViolationProvider(): iterable
    {
        yield 'file path cannot leave configured base directory' => [
            static function () {
                $base = Path::dir('C:/Projects/app/storage/', PathFormat::REFERENCE_PATH);

                return Path::file('C:/Projects/app/storage/logs/error.log', PathFormat::REFERENCE_PATH)->withBaseDir($base);
            },
            '../../..',
        ];
        yield 'directory path blocked at base boundary' => [
            static function () {
                $base = Path::dir('C:/Projects/app/storage/', PathFormat::REFERENCE_PATH);

                return Path::dir('C:/Projects/app/storage/logs/', PathFormat::REFERENCE_PATH)->withBaseDir($base);
            },
            '../..',
        ];
    }

    /**
     * @dataProvider cdWithBaseDirProvider
     */
    public function testCdWithBaseDirAllowsAbsoluteNavigation(callable $factory, string $relative, string $expectedReference, string $expectedAccess, string $expectedClass): void
    {
        $path = $factory();

        $result = $path->cd($relative);

        $this->assertInstanceOf($expectedClass, $result);
        $this->assertSame($expectedReference, $result->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expectedAccess, $result->toString(PathFormat::ACCESS_PATH));
    }

    public static function cdWithBaseDirProvider(): iterable
    {
        yield 'file path respects base root when using absolute navigation' => [
            static function () {
                $base = Path::dir('C:/Projects/app/', PathFormat::REFERENCE_PATH);

                return Path::file('C:/Projects/app/storage/logs/error.log', PathFormat::REFERENCE_PATH)->withBaseDir($base);
            },
            '/config/app.php',
            'C:/Projects/app/config/app.php',
            'C:/Projects/app/config/app.php',
            FilePath::class,
        ];
        yield 'unc directory absolute navigation stays inside base' => [
            static function () {
                $base = Path::dir('//server/share/app/', PathFormat::REFERENCE_PATH);

                return Path::dir('//server/share/app/releases/2024/', PathFormat::REFERENCE_PATH)->withBaseDir($base);
            },
            '/logs/',
            '//server/share/app/logs/',
            '\\\\server\\share\\app\\logs\\',
            DirectoryPath::class,
        ];
    }

    public function testFileCdEmptyStringReturnsParentDirectory(): void
    {
        $file = Path::file('/var/www/html/index.php', PathFormat::REFERENCE_PATH);

        $directory = $file->cd('');

        $this->assertInstanceOf(DirectoryPath::class, $directory);
        $this->assertSame('/var/www/html/', $directory->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('/var/www/html/', $directory->toString(PathFormat::ACCESS_PATH));
    }

    public function testDirectoryCdEmptyStringReturnsSameInstance(): void
    {
        $directory = Path::dir('/var/www/html/', PathFormat::REFERENCE_PATH);

        $result = $directory->cd('');

        $this->assertSame($directory, $result);
    }

    public function testWithBaseDirRejectsDifferentOrigin(): void
    {
        $base = Path::dir('//server/share/app/', PathFormat::REFERENCE_PATH);
        $file = Path::file('C:/Projects/app/index.php', PathFormat::REFERENCE_PATH);

        $this->expectException(DifferentOriginException::class);
        $file->withBaseDir($base);
    }

    public function testWithBaseDirRejectsPathOutsideBase(): void
    {
        $base = Path::dir('C:/Projects/app/storage/', PathFormat::REFERENCE_PATH);
        $file = Path::file('C:/Projects/app/config/app.php', PathFormat::REFERENCE_PATH);

        $this->expectException(OutOfBoundsException::class);
        $file->withBaseDir($base);
    }

    /**
     * @dataProvider withDirectoryProvider
     */
    public function testWithDirectoryReplacesParentSegments(callable $factory, string|DirectoryPath $directory, string $expectedReference, string $expectedAccess): void
    {
        $file = $factory();

        $moved = $file->withDirectory($directory);

        $this->assertInstanceOf(FilePath::class, $moved);
        $this->assertSame($expectedReference, $moved->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expectedAccess, $moved->toString(PathFormat::ACCESS_PATH));
    }

    public static function withDirectoryProvider(): iterable
    {
        yield 'switch to resources directory' => [
            static fn () => Path::file('C:/Projects/app/src/bootstrap.php', PathFormat::REFERENCE_PATH),
            Path::dir('C:/Projects/app/resources/views/', PathFormat::REFERENCE_PATH),
            'C:/Projects/app/resources/views/bootstrap.php',
            'C:\\Projects\\app\\resources\\views\\bootstrap.php',
        ];
        yield 'accepts string directory input' => [
            static fn () => Path::file('C:/Projects/app/src/bootstrap.php', PathFormat::REFERENCE_PATH),
            'C:/Projects/app/public/',
            'C:/Projects/app/public/bootstrap.php',
            'C:\\Projects\\app\\public\\bootstrap.php',
        ];
        yield 'works with unc paths' => [
            static fn () => Path::file('//server/share/app/bin/run.exe', PathFormat::REFERENCE_PATH),
            Path::dir('//server/share/app/tools/', PathFormat::REFERENCE_PATH),
            '//server/share/app/tools/run.exe',
            '\\server\\share\\app\\tools\\run.exe',
        ];
    }

    public function testWithDirectoryRejectsDifferentOrigin(): void
    {
        $file = Path::file('C:/Projects/app/index.php', PathFormat::REFERENCE_PATH);
        $directory = Path::dir('//server/share/app/', PathFormat::REFERENCE_PATH);

        $this->expectException(DifferentOriginException::class);
        $file->withDirectory($directory);
    }
}
