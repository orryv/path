<?php

namespace Tests\Integration;

use Orryv\Path;
use Orryv\Path\DirectoryPath;
use Orryv\Path\Enums\PathFormat;
use Orryv\Path\FilePath;
use PHPUnit\Framework\TestCase;

class FilesystemRelativeFormattingExtendedTest extends TestCase
{
    /**
     * @dataProvider relativeUriProvider
     */
    public function testRelativePathFromAccessUriEncodesSegments(callable $factory, string|DirectoryPath|FilePath $base, string $expected): void
    {
        $path = $factory();

        $this->assertSame($expected, $path->getRelativePathFrom($base, PathFormat::ACCESS_URI));
    }

    public static function relativeUriProvider(): iterable
    {
        yield 'windows file with space in segment' => [
            static fn () => Path::file('C:/Projects/App/data/report final.txt', PathFormat::REFERENCE_PATH),
            'C:/Projects/App/',
            'data/report final.txt',
        ];

        yield 'windows file with unicode segment' => [
            static fn () => Path::file('C:/Projects/App/Δelta/config.ini', PathFormat::REFERENCE_PATH),
            Path::dir('C:/Projects/App/', PathFormat::REFERENCE_PATH),
            'Δelta/config.ini',
        ];

        yield 'unc file with space in share segment' => [
            static fn () => Path::file('//server/share/QA Reports/summary.docx', PathFormat::REFERENCE_PATH),
            '//server/share/',
            'QA Reports/summary.docx',
        ];

        yield 'unc file with unicode characters' => [
            static fn () => Path::file('//server/share/data/Δelta/report.csv', PathFormat::REFERENCE_PATH),
            Path::dir('//server/share/data/', PathFormat::REFERENCE_PATH),
            'Δelta/report.csv',
        ];

        yield 'posix file with spaces and unicode characters' => [
            static fn () => Path::file('/var/www/My Site/über uns.html', PathFormat::REFERENCE_PATH),
            '/var/www/',
            'My Site/über uns.html',
        ];

        yield 'identical directory renders empty relative path' => [
            static fn () => Path::dir('/var/www/html/', PathFormat::REFERENCE_PATH),
            Path::dir('/var/www/html/', PathFormat::REFERENCE_PATH),
            '',
        ];
    }

    /**
     * @dataProvider relativeAccessProvider
     */
    public function testRelativePathFromAccessPathUsesNativeSeparators(callable $factory, string|DirectoryPath|FilePath $base, string $expected): void
    {
        $path = $factory();

        $this->assertSame($expected, $path->getRelativePathFrom($base, PathFormat::ACCESS_PATH));
    }

    public static function relativeAccessProvider(): iterable
    {
        yield 'windows file preserves backslashes' => [
            static fn () => Path::file('C:/Projects/App/data/report final.txt', PathFormat::REFERENCE_PATH),
            'C:/Projects/App/',
            'data/report final.txt',
        ];

        yield 'windows unicode segment keeps native separator' => [
            static fn () => Path::file('C:/Projects/App/Δelta/config.ini', PathFormat::REFERENCE_PATH),
            Path::dir('C:/Projects/App/', PathFormat::REFERENCE_PATH),
            'Δelta/config.ini',
        ];

        yield 'unc path uses backslash separators' => [
            static fn () => Path::file('//server/share/QA Reports/summary.docx', PathFormat::REFERENCE_PATH),
            '//server/share/',
            'QA Reports\\summary.docx',
        ];

        yield 'posix path keeps forward slashes' => [
            static fn () => Path::file('/var/www/My Site/über uns.html', PathFormat::REFERENCE_PATH),
            '/var/www/',
            'My Site/über uns.html',
        ];

        yield 'identical directories produce empty string' => [
            static fn () => Path::dir('/var/www/html/', PathFormat::REFERENCE_PATH),
            Path::dir('/var/www/html/', PathFormat::REFERENCE_PATH),
            '',
        ];
    }

    /**
     * @dataProvider commonBaseUriProvider
     */
    public function testGetCommonBasePathRendersAccessUri(callable $firstFactory, callable $secondFactory, string $expectedUri): void
    {
        $first = $firstFactory();
        $second = $secondFactory();

        $common = $first->getCommonBasePath($second, PathFormat::ACCESS_URI);

        $this->assertSame($expectedUri, $common->toString(PathFormat::ACCESS_URI));
    }

    public static function commonBaseUriProvider(): iterable
    {
        yield 'windows files share project directory' => [
            static fn () => Path::file('C:/Projects/App/src/index.php', PathFormat::REFERENCE_PATH),
            static fn () => Path::file('C:/Projects/App/tests/AppTest.php', PathFormat::REFERENCE_PATH),
            'file:///C:/Projects/App/',
        ];

        yield 'unc files share application directory' => [
            static fn () => Path::file('//server/share/app/releases/2024/build.zip', PathFormat::REFERENCE_PATH),
            static fn () => Path::file('//server/share/app/config/app.json', PathFormat::REFERENCE_PATH),
            'file://server/share/app/',
        ];

        yield 'posix files share /var/www' => [
            static fn () => Path::file('/var/www/html/index.php', PathFormat::REFERENCE_PATH),
            static fn () => Path::file('/var/www/assets/app.js', PathFormat::REFERENCE_PATH),
            'file:///var/www/',
        ];
    }
}
