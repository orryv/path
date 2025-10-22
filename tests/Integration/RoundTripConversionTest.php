<?php

namespace Tests\Integration;

use Orryv\Path;
use Orryv\Path\Enums\PathFormat;
use PHPUnit\Framework\TestCase;

class RoundTripConversionTest extends TestCase
{
    /**
     * @dataProvider pathMatrixProvider
     */
    public function testRoundTripNormalizedReferencePath(callable $factory, string $input, PathFormat $format, array $expected): void
    {
        $path = $factory($input, $format);

        $this->assertSame($expected['reference'], $path->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expected['access'], $path->toString(PathFormat::ACCESS_PATH));
        $this->assertSame($expected['uri'], $path->toString(PathFormat::ACCESS_URI));

        foreach ([PathFormat::ACCESS_PATH, PathFormat::REFERENCE_PATH, PathFormat::ACCESS_URI] as $targetFormat) {
            $representation = match ($targetFormat) {
                PathFormat::ACCESS_PATH => $expected['access'],
                PathFormat::REFERENCE_PATH => $expected['reference'],
                PathFormat::ACCESS_URI => $expected['uri'],
            };

            $roundTrip = $factory($representation, $targetFormat);

            $this->assertSame(
                $expected['reference'],
                $roundTrip->toString(PathFormat::REFERENCE_PATH),
                sprintf('Round-trip from %s did not normalize to the reference path', $targetFormat->name)
            );

            $this->assertSame(
                $representation,
                $roundTrip->toString($targetFormat),
                sprintf('Round-trip from %s altered the source representation', $targetFormat->name)
            );
        }
    }

    public static function pathMatrixProvider(): iterable
    {
        $fileFactory = static fn (string $value, PathFormat $format) => Path::file($value, $format);
        $dirFactory = static fn (string $value, PathFormat $format) => Path::dir($value, $format);
        $urlFactory = static fn (string $value, PathFormat $format) => Path::url($value, $format);

        return [
            'windows file (access path input)' => [
                $fileFactory,
                'C:\\Program Files\\App\\file.txt',
                PathFormat::ACCESS_PATH,
                [
                    'reference' => 'C:/Program Files/App/file.txt',
                    'access' => 'C:\\Program Files\\App\\file.txt',
                    'uri' => 'file:///C:/Program%20Files/App/file.txt',
                ],
            ],
            'windows file (reference path input with lower drive)' => [
                $fileFactory,
                'c:/Users/Public/Documents/Report.docx',
                PathFormat::REFERENCE_PATH,
                [
                    'reference' => 'C:/Users/Public/Documents/Report.docx',
                    'access' => 'C:\\Users\\Public\\Documents\\Report.docx',
                    'uri' => 'file:///C:/Users/Public/Documents/Report.docx',
                ],
            ],
            'windows file (access uri input)' => [
                $fileFactory,
                'file:///C:/Program%20Files/App/file.txt',
                PathFormat::ACCESS_URI,
                [
                    'reference' => 'C:/Program Files/App/file.txt',
                    'access' => 'C:\\Program Files\\App\\file.txt',
                    'uri' => 'file:///C:/Program%20Files/App/file.txt',
                ],
            ],
            'windows directory (reference path input)' => [
                $dirFactory,
                'C:/Projects/Demo/',
                PathFormat::REFERENCE_PATH,
                [
                    'reference' => 'C:/Projects/Demo/',
                    'access' => 'C:\\Projects\\Demo\\',
                    'uri' => 'file:///C:/Projects/Demo/',
                ],
            ],
            'posix file (access path input)' => [
                $fileFactory,
                '/var/www/html/index.html',
                PathFormat::ACCESS_PATH,
                [
                    'reference' => '/var/www/html/index.html',
                    'access' => '/var/www/html/index.html',
                    'uri' => 'file:///var/www/html/index.html',
                ],
            ],
            'posix directory (reference path input)' => [
                $dirFactory,
                '/srv/www/',
                PathFormat::REFERENCE_PATH,
                [
                    'reference' => '/srv/www/',
                    'access' => '/srv/www/',
                    'uri' => 'file:///srv/www/',
                ],
            ],
            'unc file (access path input)' => [
                $fileFactory,
                '\\server\\share\\folder\\report.pdf',
                PathFormat::ACCESS_PATH,
                [
                    'reference' => '//server/share/folder/report.pdf',
                    'access' => '\\server\\share\\folder\\report.pdf',
                    'uri' => 'file://server/share/folder/report.pdf',
                ],
            ],
            'unc directory (reference path input)' => [
                $dirFactory,
                '//server/share/folder/',
                PathFormat::REFERENCE_PATH,
                [
                    'reference' => '//server/share/folder/',
                    'access' => '\\server\\share\\folder\\',
                    'uri' => 'file://server/share/folder/',
                ],
            ],
            'unc path with spaces (access path input)' => [
                $dirFactory,
                '\\server\\share\\My Documents\\',
                PathFormat::ACCESS_PATH,
                [
                    'reference' => '//server/share/My Documents/',
                    'access' => '\\server\\share\\My Documents\\',
                    'uri' => 'file://server/share/My%20Documents/',
                ],
            ],
            'unc extended-length path (access path input)' => [
                $fileFactory,
                '\\?\\UNC\\server\\share\\folder\\deep\\file.txt',
                PathFormat::ACCESS_PATH,
                [
                    'reference' => '//server/share/folder/deep/file.txt',
                    'access' => '\\?\\UNC\\server\\share\\folder\\deep\\file.txt',
                    'uri' => 'file://server/share/folder/deep/file.txt',
                ],
            ],
            'unc file (access uri input)' => [
                $fileFactory,
                'file://server/share/folder/report.pdf',
                PathFormat::ACCESS_URI,
                [
                    'reference' => '//server/share/folder/report.pdf',
                    'access' => '\\server\\share\\folder\\report.pdf',
                    'uri' => 'file://server/share/folder/report.pdf',
                ],
            ],
            'http url (access uri input)' => [
                $urlFactory,
                'https://ex.com/a%20b/c?x=1%20y#%C3%A4',
                PathFormat::ACCESS_URI,
                [
                    'reference' => 'https://ex.com/a b/c?x=1 y#ä',
                    'access' => 'https://ex.com/a%20b/c?x=1%20y#%C3%A4',
                    'uri' => 'https://ex.com/a%20b/c?x=1%20y#%C3%A4',
                ],
            ],
            'http url (reference path input)' => [
                $urlFactory,
                'https://ex.com/a b/c?x=1 y#ä',
                PathFormat::REFERENCE_PATH,
                [
                    'reference' => 'https://ex.com/a b/c?x=1 y#ä',
                    'access' => 'https://ex.com/a%20b/c?x=1%20y#%C3%A4',
                    'uri' => 'https://ex.com/a%20b/c?x=1%20y#%C3%A4',
                ],
            ],
            'http url with user info and port (access uri input)' => [
                $urlFactory,
                'https://user:pass@example.com:8443/a%20b/index.html',
                PathFormat::ACCESS_URI,
                [
                    'reference' => 'https://user:pass@example.com:8443/a b/index.html',
                    'access' => 'https://user:pass@example.com:8443/a%20b/index.html',
                    'uri' => 'https://user:pass@example.com:8443/a%20b/index.html',
                ],
            ],
            'ftp url (reference path input)' => [
                $urlFactory,
                'ftp://downloads.example.com/public/archive 2023.zip',
                PathFormat::REFERENCE_PATH,
                [
                    'reference' => 'ftp://downloads.example.com/public/archive 2023.zip',
                    'access' => 'ftp://downloads.example.com/public/archive%202023.zip',
                    'uri' => 'ftp://downloads.example.com/public/archive%202023.zip',
                ],
            ],
            'posix directory with unicode (access path input)' => [
                $dirFactory,
                '/srv/www/år/',
                PathFormat::ACCESS_PATH,
                [
                    'reference' => '/srv/www/år/',
                    'access' => '/srv/www/år/',
                    'uri' => 'file:///srv/www/%C3%A5r/',
                ],
            ],
            'posix file with spaces (access path input)' => [
                $fileFactory,
                '/opt/data/My Documents/report.txt',
                PathFormat::ACCESS_PATH,
                [
                    'reference' => '/opt/data/My Documents/report.txt',
                    'access' => '/opt/data/My Documents/report.txt',
                    'uri' => 'file:///opt/data/My%20Documents/report.txt',
                ],
            ],
            'posix file (access uri input)' => [
                $fileFactory,
                'file:///etc/cron.d/daily%20job',
                PathFormat::ACCESS_URI,
                [
                    'reference' => '/etc/cron.d/daily job',
                    'access' => '/etc/cron.d/daily job',
                    'uri' => 'file:///etc/cron.d/daily%20job',
                ],
            ],
            'windows extended-length drive path (access path input)' => [
                $fileFactory,
                '\\?\\C:\\very\\deep\\folder\\file.bin',
                PathFormat::ACCESS_PATH,
                [
                    'reference' => 'C:/very/deep/folder/file.bin',
                    'access' => '\\?\\C:\\very\\deep\\folder\\file.bin',
                    'uri' => 'file:///C:/very/deep/folder/file.bin',
                ],
            ],
        ];
    }
}
