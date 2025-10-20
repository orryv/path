<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Orryv\Path;
use Orryv\Path\Exceptions\UnknownIfFolderOrFileException;
use Orryv\Path\Exceptions\AboveBaseFolderException;
use Orryv\Path\Paths\AbsoluteUnixPath;
use Orryv\Path\Enums\SystemPathLocationCategory;
use Orryv\Path\Enums\OSFamily;
use Orryv\Path\Models\AbsoluteReferencePathFormat;
use Orryv\Path\Models\AbsoluteAccessPathFormat;
use Orryv\Path\Models\AbsoluteAccessURIFormat;

class NavigationTest extends TestCase
{
    ################
    ###### CD ######
    ################

    public function testIfCdGoesUpOneLevel()
    {
        $old_instance = Path::create(new AbsoluteReferencePathFormat('/var/bin/file.txt'))->asFile();
        $new_instance = $old_instance->cd('..');
        $expected = '/var';
        $this->assertEquals($expected, $new_instance->getReferencePath());
    }

    public function testExceptionWhenNotAsFileOrFolder()
    {
        $this->expectException(UnknownIfFolderOrFileException::class);
        $uri = Path::create(new AbsoluteReferencePathFormat('/var/bin/file.txt'));
        $uri->cd('..');
    }

    public function testIfCdGoesUpTwoLevels()
    {
        $old_instance = Path::create(new AbsoluteReferencePathFormat('/var/bin/file.txt'))->asFile();
        $new_instance = $old_instance->cd('../..');
        $expected = '/';
        $this->assertEquals($expected, $new_instance->getReferencePath());
    }

    public function testIfCdGoesDownOneLevel()
    {
        $old_instance = Path::create(new AbsoluteReferencePathFormat('/var/bin/file.txt'))->asFile();
        $new_instance = $old_instance->cd('foo')->asFolder();
        $expected = '/var/bin/foo';
        $this->assertEquals($expected, $new_instance->getReferencePath());
        $this->assertEquals(['var', 'bin', 'foo'], $new_instance->getPath());
        $this->assertEquals(['var', 'bin', 'foo'], $new_instance->getFolderPath());
    }

    public function testIfItGoesUpTwoTimesInDifferentCalls()
    {
        $old_instance = Path::create(new AbsoluteReferencePathFormat('/var/bin/file.txt'))->asFile();
        $new_instance = $old_instance->cd('..')->cd('..');
        $expected = '/';
        $this->assertEquals($expected, $new_instance->getReferencePath());
    }

    public function testIfAsFolderChangesPath()
    {
        $old_instance = Path::create(new AbsoluteReferencePathFormat('/var/bin/file.txt'))->asFile();
        $expected = '/var/bin/file.txt';
        $this->assertEquals($expected, $old_instance->getReferencePath());
    }

    public function testIfExceptionWhenAboveBaseFolder()
    {
        $base_folder = Path::create(new AbsoluteReferencePathFormat('/var/bin/file.txt'))->asFile();
        $this->expectException(AboveBaseFolderException::class);
        $instance = Path::create(new AbsoluteReferencePathFormat('/var/bin/folder/file.txt'))
            ->setBasePath($base_folder)
            ->asFile();
        $new = $instance->cd('../..');
    }

    public function testIfNavigatesToFolders()
    {
        $old_instance = Path::create(new AbsoluteReferencePathFormat('/var/bin/file.txt'))->asFile();
        $new_instance = $old_instance->cd('foo/bar')->asFolder();
        $expected = '/var/bin/foo/bar';
        $this->assertEquals($expected, $new_instance->getReferencePath());
    }

    public function testIfGoesToBaseFolder()
    {
        $base_folder = Path::create(new AbsoluteReferencePathFormat('/var/bin/file.txt'))->asFile();
        $instance = Path::create(new AbsoluteReferencePathFormat('/var/bin/folder/file.txt'))
            ->setBasePath($base_folder)
            ->asFile();
        $new = $instance->cd('/');
        $expected = '/var/bin';
        $this->assertEquals($expected, $new->getReferencePath());
    }

    public function testIfGoesToFolderInBaseFolder()
    {
        $base_folder = Path::create(new AbsoluteReferencePathFormat('/var/bin/file.txt'))->asFile();
        $instance = Path::create(new AbsoluteReferencePathFormat('/var/bin/folder/bar/file.txt'))
            ->setBasePath($base_folder)
            ->asFile();
        $new = $instance->cd('/test');
        $expected = '/var/bin/test';
        $this->assertEquals($expected, $new->getReferencePath());
    }

    public function testIfGoesToRootFolder()
    {
        $instance = Path::create(new AbsoluteReferencePathFormat('/var/bin/folder/file.txt'))
            ->asFile();
        $new = $instance->cd('/');
        $expected = '/';
        $this->assertEquals($expected, $new->getReferencePath());
    }

    public function testIfGoesToFolderInRootFolder()
    {
        $instance = Path::create(new AbsoluteReferencePathFormat('/var/bin/folder/file.txt'))
            ->asFile();
        $new = $instance->cd('/test');
        $expected = '/test';
        $this->assertEquals($expected, $new->getReferencePath());
    }

    public function testIfExceptionWhenAboveBaseFolderInRootFolder()
    {
        $this->expectException(AboveBaseFolderException::class);
        $base_folder = Path::create(new AbsoluteReferencePathFormat('/var/bin/file.txt'))->asFile();
        $instance = Path::create(new AbsoluteReferencePathFormat('/var/bin/folder/file.txt'))
            ->setBasePath($base_folder)
            ->asFile();
        $new = $instance->cd('/..');
    }

    public function testAsDotDetectsFiles()
    {
        $path = Path::create(new AbsoluteReferencePathFormat('https://example.com/assets/image.png?ver=1#fragment'))
            ->asDot();

        $this->assertEquals('image', $path->getReferencePathFileName());
        $this->assertEquals('png', $path->getReferencePathFileExtension());
    }

    public function testAsDotTreatsFoldersWithoutDots()
    {
        $path = Path::create(new AbsoluteReferencePathFormat('https://example.com/assets/'))
            ->asDot();

        $this->assertEquals(['assets'], $path->getFolderPath());
        $this->assertNull($path->getReferencePathFileName());
    }

    public function testPreserveEndSlashAddsTrailingSeparators()
    {
        $path = Path::create(new AbsoluteReferencePathFormat('/var/www'))
            ->asFolder()
            ->preserveEndSlash();

        $this->assertEquals('/var/www/', $path->getAccessPath());
        $this->assertEquals('file:///var/www/', $path->getAccessURI());
        $this->assertEquals(['var', 'www'], $path->getFolderPath());
    }

    public function testPreserveEndSlashDoesNotAffectFiles()
    {
        $path = Path::create(new AbsoluteReferencePathFormat('/var/www/file.txt'))
            ->asFile()
            ->preserveEndSlash();

        $this->assertEquals('/var/www/file.txt', $path->getAccessPath());
    }

    public function testFolderHelpersReturnFormattedPaths()
    {
        $path = Path::create(new AbsoluteReferencePathFormat('/var/www/html/index.php'))
            ->asFile();

        $this->assertSame('var', $path->getFirstFolder());
        $this->assertSame('www', $path->getNthFolder(1));
        $this->assertSame('html', $path->getLastFolder());

        $this->assertInstanceOf(AbsoluteReferencePathFormat::class, $path->getFirstFolder(AbsoluteReferencePathFormat::class));
        $this->assertInstanceOf(AbsoluteAccessPathFormat::class, $path->getFirstFolder(AbsoluteAccessPathFormat::class));
        $this->assertInstanceOf(AbsoluteAccessURIFormat::class, $path->getFirstFolder(AbsoluteAccessURIFormat::class));

        $this->assertEquals('/var', (string) $path->getFirstFolder(AbsoluteReferencePathFormat::class));
        $this->assertEquals('/var/www', (string) $path->getNthFolder(1, AbsoluteReferencePathFormat::class));
        $this->assertEquals('/var/www/html', (string) $path->getLastFolder(AbsoluteReferencePathFormat::class));
        $this->assertEquals('file:///var', (string) $path->getFirstFolder(AbsoluteAccessURIFormat::class));
        $this->assertEquals('/var', (string) $path->getFirstFolder(AbsoluteAccessPathFormat::class));
        $this->assertEquals(3, $path->getFolderCount());
        $this->assertNull($path->getNthFolder(10));
    }

    public function testGetPathAndFolderPathFormats()
    {
        $path = Path::create(new AbsoluteReferencePathFormat('/var/www/html/index.php'))
            ->asFile();

        $this->assertSame(['var', 'www', 'html', 'index.php'], $path->getPath());
        $this->assertSame(['var', 'www', 'html'], $path->getFolderPath());

        $this->assertEquals($path->getReferencePath(), $path->getPath(AbsoluteReferencePathFormat::class));
        $this->assertEquals($path->getAccessPath(), $path->getPath(AbsoluteAccessPathFormat::class));
        $this->assertEquals($path->getAccessURI(), $path->getPath(AbsoluteAccessURIFormat::class));

        $this->assertEquals('/var/www/html', (string) $path->getFolderPath(AbsoluteReferencePathFormat::class));
        $this->assertEquals('/var/www/html', (string) $path->getFolderPath(AbsoluteAccessPathFormat::class));
        $this->assertEquals('file:///var/www/html', (string) $path->getFolderPath(AbsoluteAccessURIFormat::class));
    }
}
