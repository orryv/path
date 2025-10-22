<?php

namespace Tests\Integration;

use Orryv\Path;
use Orryv\Path\DirectoryPath;
use Orryv\Path\Enums\PathFormat;
use Orryv\Path\Exceptions\DifferentOriginException;
use Orryv\Path\FilePath;
use Orryv\Path\Support\Unicode;
use PHPUnit\Framework\TestCase;

class FilesystemMutationTest extends TestCase
{
    /**
     * @dataProvider withDirectoryProvider
     */
    public function testWithDirectoryRebasesFilePath(callable $factory, string $input, PathFormat $format, string|DirectoryPath $directory, array $expected): void
    {
        $path = $factory($input, $format);

        $rebased = $path->withDirectory($directory);

        $this->assertInstanceOf(FilePath::class, $rebased);
        $this->assertSame($expected['reference'], $rebased->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expected['access'], $rebased->toString(PathFormat::ACCESS_PATH));
        $this->assertSame($expected['uri'], $rebased->toString(PathFormat::ACCESS_URI));
    }

    public static function withDirectoryProvider(): iterable
    {
        $fileFactory = static fn (string $value, PathFormat $format): FilePath => Path::file($value, $format);

        $segments = array_map(static fn (int $index): string => 'segment' . $index, range(1, 18));
        $segments[4] = 'Ångström';
        $segments[9] = '数据';
        $escapedSegments = array_map(
            static fn (string $segment): string => preg_match('/[^\\x00-\\x7F]/u', $segment) === 1
                ? Unicode::escapeSegment($segment, true)
                : $segment,
            $segments
        );
        $encodedSegments = array_map('rawurlencode', $segments);
        $longReferenceDirectory = 'C:/' . implode('/', $segments) . '/target/';
        $longReferenceFile = $longReferenceDirectory . 'archive/report.txt';

        return [
            'windows access path to reference directory' => [
                $fileFactory,
                'C:\\Projects\\App\\src\\index.php',
                PathFormat::ACCESS_PATH,
                'C:/Projects/App/tests/',
                [
                    'reference' => 'C:/Projects/App/tests/index.php',
                    'access' => 'C:\\Projects\\App\\tests\\index.php',
                    'uri' => 'file:///C:/Projects/App/tests/index.php',
                ],
            ],
            'windows reference path to directory object' => [
                $fileFactory,
                'C:/Projects/App/bin/console',
                PathFormat::REFERENCE_PATH,
                Path::dir('C:/Projects/App/runtime/', PathFormat::REFERENCE_PATH),
                [
                    'reference' => 'C:/Projects/App/runtime/console',
                    'access' => 'C:\\Projects\\App\\runtime\\console',
                    'uri' => 'file:///C:/Projects/App/runtime/console',
                ],
            ],
            'posix file into cache directory' => [
                $fileFactory,
                '/var/www/html/index.php',
                PathFormat::REFERENCE_PATH,
                Path::dir('/var/www/cache/', PathFormat::REFERENCE_PATH),
                [
                    'reference' => '/var/www/cache/index.php',
                    'access' => '/var/www/cache/index.php',
                    'uri' => 'file:///var/www/cache/index.php',
                ],
            ],
            'unc report moved to archive' => [
                $fileFactory,
                '//server/share/reports/2023/Q1.xlsx',
                PathFormat::REFERENCE_PATH,
                Path::dir('//server/share/archive/2023/', PathFormat::REFERENCE_PATH),
                [
                    'reference' => '//server/share/archive/2023/Q1.xlsx',
                    'access' => '\\server\\share\\archive\\2023\\Q1.xlsx',
                    'uri' => 'file://server/share/archive/2023/Q1.xlsx',
                ],
            ],
            'long windows reference path preserves unicode segments' => [
                $fileFactory,
                $longReferenceFile,
                PathFormat::REFERENCE_PATH,
                Path::dir($longReferenceDirectory, PathFormat::REFERENCE_PATH),
                [
                    'reference' => $longReferenceDirectory . 'report.txt',
                    'access' => 'C:\\' . implode('\\', $escapedSegments) . '\\target\\report.txt',
                    'uri' => 'file:///C:/' . implode('/', $encodedSegments) . '/target/report.txt',
                ],
            ],
        ];
    }

    /**
     * @dataProvider withDirectoryDifferentOriginProvider
     */
    public function testWithDirectoryRejectsDifferentOrigin(callable $factory, string $input, PathFormat $format, string|DirectoryPath $directory): void
    {
        $path = $factory($input, $format);

        $this->expectException(DifferentOriginException::class);
        $path->withDirectory($directory);
    }

    public static function withDirectoryDifferentOriginProvider(): iterable
    {
        $fileFactory = static fn (string $value, PathFormat $format): FilePath => Path::file($value, $format);

        return [
            'different windows drives' => [
                $fileFactory,
                'C:/Projects/App/index.php',
                PathFormat::REFERENCE_PATH,
                'D:/Other/location/',
            ],
            'windows file to unc directory' => [
                $fileFactory,
                'C:/Projects/App/index.php',
                PathFormat::REFERENCE_PATH,
                Path::dir('//server/share/app/', PathFormat::REFERENCE_PATH),
            ],
            'posix to windows drive' => [
                $fileFactory,
                '/var/www/html/index.php',
                PathFormat::REFERENCE_PATH,
                'C:/Projects/App/',
            ],
            'unc to different share' => [
                $fileFactory,
                '//server/share/docs/readme.txt',
                PathFormat::REFERENCE_PATH,
                Path::dir('//server/other/docs/', PathFormat::REFERENCE_PATH),
            ],
        ];
    }

    /**
     * @dataProvider cdScenarioProvider
     */
    public function testCdResolvesComplexPaths(callable $factory, string $input, PathFormat $format, string $relative, string $expectedClass, array $expected): void
    {
        $path = $factory($input, $format);

        $result = $path->cd($relative);

        $this->assertInstanceOf($expectedClass, $result);
        $this->assertSame($expected['reference'], $result->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expected['access'], $result->toString(PathFormat::ACCESS_PATH));
        $this->assertSame($expected['uri'], $result->toString(PathFormat::ACCESS_URI));
    }

    public static function cdScenarioProvider(): iterable
    {
        $fileFactory = static fn (string $value, PathFormat $format): FilePath => Path::file($value, $format);
        $dirFactory = static fn (string $value, PathFormat $format): DirectoryPath => Path::dir($value, $format);

        $layerSegments = array_map(static fn (int $i): string => 'layer' . $i, range(1, 12));
        $windowsLongBase = 'C:/Projects/' . implode('/', $layerSegments) . '/';
        $windowsLongFile = $windowsLongBase . 'reports/today.log';

        return [
            'windows file to sibling config' => [
                $fileFactory,
                'C:/Projects/App/index.php',
                PathFormat::REFERENCE_PATH,
                'config/app.php',
                FilePath::class,
                [
                    'reference' => 'C:/Projects/App/config/app.php',
                    'access' => 'C:\\Projects\\App\\config\\app.php',
                    'uri' => 'file:///C:/Projects/App/config/app.php',
                ],
            ],
            'windows file up one directory' => [
                $fileFactory,
                'C:/Projects/App/src/index.php',
                PathFormat::REFERENCE_PATH,
                '..',
                DirectoryPath::class,
                [
                    'reference' => 'C:/Projects/App/src/',
                    'access' => 'C:\\Projects\\App\\src\\',
                    'uri' => 'file:///C:/Projects/App/src/',
                ],
            ],
            'windows directory normalizes dot segments' => [
                $dirFactory,
                'C:/Projects/App/storage/',
                PathFormat::REFERENCE_PATH,
                './logs/../cache/temp',
                DirectoryPath::class,
                [
                    'reference' => 'C:/Projects/App/storage/cache/temp/',
                    'access' => 'C:\\Projects\\App\\storage\\cache\\temp\\',
                    'uri' => 'file:///C:/Projects/App/storage/cache/temp/',
                ],
            ],
            'unc file navigates to archive' => [
                $fileFactory,
                '//server/share/dept/reports/2023/Q1.xlsx',
                PathFormat::REFERENCE_PATH,
                '../../../archive/Q1.xlsx',
                FilePath::class,
                [
                    'reference' => '//server/share/dept/archive/Q1.xlsx',
                    'access' => '\\server\\share\\dept\\archive\\Q1.xlsx',
                    'uri' => 'file://server/share/dept/archive/Q1.xlsx',
                ],
            ],
            'unc directory resolves absolute path' => [
                $dirFactory,
                '//server/share/dept/reports/',
                PathFormat::REFERENCE_PATH,
                '/dept/archive/2023/',
                DirectoryPath::class,
                [
                    'reference' => '//server/share/dept/archive/2023/',
                    'access' => '\\server\\share\\dept\\archive\\2023\\',
                    'uri' => 'file://server/share/dept/archive/2023/',
                ],
            ],
            'posix directory collapses complex segments' => [
                $dirFactory,
                '/opt/app/runtime/',
                PathFormat::REFERENCE_PATH,
                '../log/./today/../yesterday/',
                DirectoryPath::class,
                [
                    'reference' => '/opt/app/log/yesterday/',
                    'access' => '/opt/app/log/yesterday/',
                    'uri' => 'file:///opt/app/log/yesterday/',
                ],
            ],
            'file empty relative returns parent directory' => [
                $fileFactory,
                '/opt/app/runtime/log.txt',
                PathFormat::REFERENCE_PATH,
                '',
                DirectoryPath::class,
                [
                    'reference' => '/opt/app/runtime/',
                    'access' => '/opt/app/runtime/',
                    'uri' => 'file:///opt/app/runtime/',
                ],
            ],
            'long windows path keeps preference during cd' => [
                $fileFactory,
                $windowsLongFile,
                PathFormat::REFERENCE_PATH,
                '../summary.txt',
                FilePath::class,
                [
                    'reference' => $windowsLongBase . 'reports/summary.txt',
                    'access' => 'C:\\Projects\\' . implode('\\', $layerSegments) . '\\reports\\summary.txt',
                    'uri' => 'file:///C:/Projects/' . implode('/', $layerSegments) . '/reports/summary.txt',
                ],
            ],
        ];
    }

    /**
     * @dataProvider cdPreventsTraversalProvider
     */
    public function testCdPreventsInvalidTraversal(callable $factory, string $input, PathFormat $format, ?callable $configure, string $relative): void
    {
        $path = $factory($input, $format);
        if ($configure !== null) {
            $path = $configure($path);
        }

        $this->expectException(\OutOfBoundsException::class);
        $path->cd($relative);
    }

    public static function cdPreventsTraversalProvider(): iterable
    {
        $fileFactory = static fn (string $value, PathFormat $format): FilePath => Path::file($value, $format);
        $dirFactory = static fn (string $value, PathFormat $format): DirectoryPath => Path::dir($value, $format);

        return [
            'windows directory cannot climb above drive root' => [
                $dirFactory,
                'C:/Projects/App/',
                PathFormat::REFERENCE_PATH,
                null,
                '../../..',
            ],
            'unc directory cannot climb above share' => [
                $dirFactory,
                '//server/share/data/',
                PathFormat::REFERENCE_PATH,
                null,
                '../../../..',
            ],
            'file with base dir prevents leaving base' => [
                $fileFactory,
                '/var/www/app/index.php',
                PathFormat::REFERENCE_PATH,
                static fn (FilePath $path): FilePath => $path->withBaseDir(Path::dir('/var/www/', PathFormat::REFERENCE_PATH)),
                '../../..',
            ],
            'directory with base dir blocks upwards traversal' => [
                $dirFactory,
                'C:/Projects/App/storage/logs/',
                PathFormat::REFERENCE_PATH,
                static fn (DirectoryPath $path): DirectoryPath => $path->withBaseDir(Path::dir('C:/Projects/App/', PathFormat::REFERENCE_PATH)),
                '../../..',
            ],
        ];
    }

    /**
     * @dataProvider windowsLongPathSupportProvider
     */
    public function testWindowsLongPathSupportToggling(callable $factory, string $input, PathFormat $format, string $expectedEnabled, string $expectedDisabled): void
    {
        $path = $factory($input, $format);
        $originalReference = $path->toString(PathFormat::REFERENCE_PATH);

        $enabled = $path->withWindowsLongPathSupport(true);
        $disabled = $enabled->withWindowsLongPathSupport(false);

        $this->assertSame($expectedEnabled, $enabled->toString(PathFormat::ACCESS_PATH));
        $this->assertSame($expectedDisabled, $disabled->toString(PathFormat::ACCESS_PATH));
        $this->assertSame($originalReference, $enabled->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($originalReference, $disabled->toString(PathFormat::REFERENCE_PATH));
    }

    public static function windowsLongPathSupportProvider(): iterable
    {
        $fileFactory = static fn (string $value, PathFormat $format): FilePath => Path::file($value, $format);
        $dirFactory = static fn (string $value, PathFormat $format): DirectoryPath => Path::dir($value, $format);

        $longSegments = array_map(static fn (int $index): string => 'folder' . $index, range(1, 40));
        $longReference = 'C:/' . implode('/', $longSegments) . '/file.txt';
        $backslash = '\\';
        $longAccess = "\\\\?\\C:\\" . implode($backslash, $longSegments) . $backslash . 'file.txt';
        $shortAccess = 'C:\\' . implode($backslash, $longSegments) . $backslash . 'file.txt';

        $uncSegments = array_map(
            static fn (int $index): string => 'department' . str_pad((string) $index, 2, '0', STR_PAD_LEFT),
            range(1, 28)
        );
        $uncReference = '//server/share/' . implode('/', $uncSegments) . '/';
        $uncAccess = "\\\\?\\UNC\\server\\share\\" . implode($backslash, $uncSegments) . $backslash;
        $uncShortAccess = '\\server\\share\\' . implode($backslash, $uncSegments) . $backslash;

        return [
            'long windows file gains prefix when enabled' => [
                $fileFactory,
                $longReference,
                PathFormat::REFERENCE_PATH,
                $longAccess,
                $shortAccess,
            ],
            'short windows file keeps standard formatting' => [
                $fileFactory,
                'C:/Projects/App/src/index.php',
                PathFormat::REFERENCE_PATH,
                'C:\\Projects\\App\\src\\index.php',
                'C:\\Projects\\App\\src\\index.php',
            ],
            'long unc directory gains UNC prefix' => [
                $dirFactory,
                $uncReference,
                PathFormat::REFERENCE_PATH,
                $uncAccess,
                $uncShortAccess,
            ],
            'existing long path prefix can be removed' => [
                $fileFactory,
                '\\?\\C:\\Projects\\Demo\\output\\report.txt',
                PathFormat::ACCESS_PATH,
                '\\?\\C:\\Projects\\Demo\\output\\report.txt',
                'C:\\Projects\\Demo\\output\\report.txt',
            ],
            'short unc path unaffected by preference' => [
                $dirFactory,
                '//server/share/data/',
                PathFormat::REFERENCE_PATH,
                '\\server\\share\\data\\',
                '\\server\\share\\data\\',
            ],
        ];
    }
}
