<?php

namespace Tests\Integration;

use Orryv\Path;
use Orryv\Path\Enums\PathFormat;
use PHPUnit\Framework\TestCase;

class FilesystemRenderingConformanceTest extends TestCase
{
    /**
     * @dataProvider directoryRenderingProvider
     */
    public function testDirectoryRenderingAcrossFormats(string $input, PathFormat $format, array $expected): void
    {
        $directory = Path::dir($input, $format);

        $this->assertSame($expected['reference'], $directory->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expected['access'], $directory->toString(PathFormat::ACCESS_PATH));
        $this->assertSame($expected['uri'], $directory->toString(PathFormat::ACCESS_URI));
    }

    public static function directoryRenderingProvider(): iterable
    {
        yield 'windows directory preserves trailing slash from reference path' => [
            'C:/Program Files/App/',
            PathFormat::REFERENCE_PATH,
            [
                'reference' => 'C:/Program Files/App/',
                'access' => 'C:\\Program Files\\App\\',
                'uri' => 'file:///C:/Program%20Files/App/',
            ],
        ];

        yield 'windows directory without trailing slash stays compact' => [
            'C:/Workspace',
            PathFormat::REFERENCE_PATH,
            [
                'reference' => 'C:/Workspace',
                'access' => 'C:\\Workspace',
                'uri' => 'file:///C:/Workspace',
            ],
        ];

        yield 'posix directory keeps separators across formats' => [
            '/var/www/html/',
            PathFormat::REFERENCE_PATH,
            [
                'reference' => '/var/www/html/',
                'access' => '/var/www/html/',
                'uri' => 'file:///var/www/html/',
            ],
        ];

        yield 'posix directory without trailing slash does not add one' => [
            '/usr/local/bin',
            PathFormat::ACCESS_PATH,
            [
                'reference' => '/usr/local/bin',
                'access' => '/usr/local/bin',
                'uri' => 'file:///usr/local/bin',
            ],
        ];

        yield 'unc directory from reference path renders expected formats' => [
            '//server/share/releases/2024/',
            PathFormat::REFERENCE_PATH,
            [
                'reference' => '//server/share/releases/2024/',
                'access' => '\\server\\share\\releases\\2024\\',
                'uri' => 'file://server/share/releases/2024/',
            ],
        ];

        yield 'unc directory with long path prefix maintains prefix' => [
            '\\?\\UNC\\server\\share\\archive\\',
            PathFormat::ACCESS_PATH,
            [
                'reference' => '//server/share/archive/',
                'access' => '\\?\\UNC\\server\\share\\archive\\',
                'uri' => 'file://server/share/archive/',
            ],
        ];

        yield 'windows extended directory keeps prefix when rendering' => [
            '\\?\\C:\\Projects\\Demo\\',
            PathFormat::ACCESS_PATH,
            [
                'reference' => 'C:/Projects/Demo/',
                'access' => '\\?\\C:\\Projects\\Demo\\',
                'uri' => 'file:///C:/Projects/Demo/',
            ],
        ];

        yield 'root directory renders consistently' => [
            '/',
            PathFormat::REFERENCE_PATH,
            [
                'reference' => '/',
                'access' => '/',
                'uri' => 'file:////',
            ],
        ];
    }

    /**
     * @dataProvider fileRenderingProvider
     */
    public function testFileRenderingAcrossFormats(string $input, PathFormat $format, array $expected): void
    {
        $file = Path::file($input, $format);

        $this->assertSame($expected['reference'], $file->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expected['access'], $file->toString(PathFormat::ACCESS_PATH));
        $this->assertSame($expected['uri'], $file->toString(PathFormat::ACCESS_URI));
    }

    public static function fileRenderingProvider(): iterable
    {
        yield 'windows file from reference path' => [
            'C:/Projects/app/config/app.php',
            PathFormat::REFERENCE_PATH,
            [
                'reference' => 'C:/Projects/app/config/app.php',
                'access' => 'C:\\Projects\\app\\config\\app.php',
                'uri' => 'file:///C:/Projects/app/config/app.php',
            ],
        ];

        yield 'windows file from access path' => [
            'C:\\Projects\\app\\config\\app.php',
            PathFormat::ACCESS_PATH,
            [
                'reference' => 'C:/Projects/app/config/app.php',
                'access' => 'C:\\Projects\\app\\config\\app.php',
                'uri' => 'file:///C:/Projects/app/config/app.php',
            ],
        ];

        yield 'windows file from access uri with spaces' => [
            'file:///C:/Program%20Files/My%20App/app.exe',
            PathFormat::ACCESS_URI,
            [
                'reference' => 'C:/Program Files/My App/app.exe',
                'access' => 'C:\\Program Files\\My App\\app.exe',
                'uri' => 'file:///C:/Program%20Files/My%20App/app.exe',
            ],
        ];

        yield 'unc file from reference path' => [
            '//server/share/app/releases/2024/build.zip',
            PathFormat::REFERENCE_PATH,
            [
                'reference' => '//server/share/app/releases/2024/build.zip',
                'access' => '\\server\\share\\app\\releases\\2024\\build.zip',
                'uri' => 'file://server/share/app/releases/2024/build.zip',
            ],
        ];

        yield 'unc file from access path with unicode characters' => [
            '\\server\\share\\data\\报告.txt',
            PathFormat::ACCESS_PATH,
            [
                'reference' => '//server/share/data/报告.txt',
                'access' => '\\server\\share\\data\\\u{62A5}\u{544A}.txt',
                'uri' => 'file://server/share/data/%E6%8A%A5%E5%91%8A.txt',
            ],
        ];

        yield 'unc file from access uri' => [
            'file://server/share/releases/2024/build.zip',
            PathFormat::ACCESS_URI,
            [
                'reference' => '//server/share/releases/2024/build.zip',
                'access' => '\\server\\share\\releases\\2024\\build.zip',
                'uri' => 'file://server/share/releases/2024/build.zip',
            ],
        ];

        yield 'posix file from reference path with unicode segment' => [
            '/var/www/html/über-uns.html',
            PathFormat::REFERENCE_PATH,
            [
                'reference' => '/var/www/html/über-uns.html',
                'access' => '/var/www/html/über-uns.html',
                'uri' => 'file:///var/www/html/%C3%BCber-uns.html',
            ],
        ];

        yield 'posix file from access uri' => [
            'file:///etc/hosts',
            PathFormat::ACCESS_URI,
            [
                'reference' => '/etc/hosts',
                'access' => '/etc/hosts',
                'uri' => 'file:///etc/hosts',
            ],
        ];
    }
}
