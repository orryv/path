<?php

use PHPUnit\Framework\TestCase;
use Orryv\Path\Path;
use Orryv\Path\Exceptions\UnknownIfFolderOrFileException;
use Orryv\Path\Exceptions\AboveBaseFolderException;
use Orryv\Path\Paths\AbsoluteUnixPath;
use Orryv\Path\Enums\SystemPathLocationCategory;
use Orryv\Path\Enums\OSFamily;
use Orryv\Path\Models\AbsoluteReferencePathFormat;

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
}