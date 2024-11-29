<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Orryv\Path\Path;
use Orryv\Path\Exceptions\UnknownIfFolderOrFileException;
use Orryv\Path\Exceptions\AboveBaseFolderException;
use Orryv\Path\Paths\AbsoluteUnixPath;
use Orryv\Path\Enums\SystemPathLocationCategory;
use Orryv\Path\Enums\OSFamily;
use Orryv\Path\Models\AbsoluteReferencePathFormat;

class PathsBase extends TestCase
{
    protected array $tests;

    #################
    #### GENERAL ####
    #################

    public function testCanParseDS()
    {
        foreach ($this->tests as $uri => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            $this->assertEquals($parts['ds'], $uri->ds());
        }
    }

    public function testCanParseScheme()
    {
        foreach ($this->tests as $uri => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            $this->assertEquals($parts['scheme'], $uri->getScheme());
        }
    }

    public function testWithSchemeReturnsNewInstance()
    {
        foreach ($this->tests as $uri => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            $new_uri = $uri->withScheme($parts['NOTSCHEME']);
            // assert that the original instance is not modified
            $this->assertEquals($parts['scheme'], $uri->getScheme());
            // assert that the new instance has the new scheme
            $this->assertEquals($parts['NOTSCHEME'], $new_uri->getScheme());
        }
    }

    public function testCanParseHost()
    {
        foreach ($this->tests as $uri => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            $this->assertEquals($parts['host'], $uri->getHost());
        }
    }

    public function testWithHostReturnsNewInstance()
    {
        foreach ($this->tests as $uri => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            $old_host = $uri->getHost();
            $new_uri = $uri->withHost(['www', 'fgsgshghfgh', 'com']);
            // assert that the original instance is not modified
            $this->assertEquals($old_host, $uri->getHost());
            // assert that the new instance has the new host
            $this->assertEquals(['www', 'fgsgshghfgh', 'com'], $new_uri->getHost());
        }
    }

    public function testCanParseHostString()
    {
        foreach ($this->tests as $uri => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            if($parts['host']){
                $this->assertEquals(implode('.', $parts['host']), $uri->getHostString());
            } else {
                $this->assertNull($uri->getHostString());
            }
        }
    }

    public function testWithHostStringReturnsNewInstance()
    {
        foreach ($this->tests as $uri => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            $old_host = $uri->getHost();
            $new_uri = $uri->withHostString('www.fgsgshghfgh.com');
            // assert that the original instance is not modified
            $this->assertEquals($old_host, $uri->getHost());
            // assert that the new instance has the new host
            $this->assertEquals(['www', 'fgsgshghfgh', 'com'], $new_uri->getHost());
        }
    }

    public function testCanParsePath()
    {
        foreach ($this->tests as $uri => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            $this->assertEquals($parts['path'], $uri->getPath());
        }
    }

    public function testWithPathReturnsNewInstance()
    {
        foreach ($this->tests as $uri => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            $original = $uri->getPath();
            $new_uri = $uri->withPath(['weird', 'fgsgshghfgh', 'file.txt']);
            // assert that the original instance is not modified
            $this->assertEquals($original, $uri->getPath());
            // assert that the new instance has the new path
            $this->assertEquals(['weird', 'fgsgshghfgh', 'file.txt'], $new_uri->getPath());
        }
    }

    ####################
    #### ACCESS URI ####
    ####################

    public function testCanParseAccessURIFileName()
    {
        foreach ($this->tests as $path => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($path));
            if($parts['is_file']){
                $uri = $uri->asFile();
            } else {
                $uri = $uri->asFolder();
            }
            $this->assertEquals($parts['access_uri_file_name'], $uri->getAccessURIFileName());

            // Check if it returns null when asFile or asFolder is not called
            $p = Path::create(new AbsoluteReferencePathFormat($path));
            $this->assertNull($p->getAccessURIFileName());
        }
    }

    public function testCanParseAccessURIFileExtension()
    {
        foreach ($this->tests as $path => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($path));
            if($parts['is_file']){
                $uri = $uri->asFile();
            } else {
                $uri = $uri->asFolder();
            }
            $this->assertEquals($parts['access_uri_file_extension'], $uri->getAccessURIFileExtension());

            // Check if it returns null when asFile or asFolder is not called
            $p = Path::create(new AbsoluteReferencePathFormat($path));
            $this->assertNull($p->getAccessURIFileName());
        }
    }

    public function testCanParseAccessURIRootFolder()
    {
        foreach ($this->tests as $uri => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            $this->assertEquals($parts['access_uri_root_folder'], $uri->getAccessURIRootFolder());
        }
    }

    public function testCanParseAccessURI()
    {
        foreach ($this->tests as $uri => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            $this->assertEquals($parts['access_uri'], $uri->getAccessURI());
        }
    }

    #####################
    #### ACCESS PATH ####
    #####################

    public function testCanParseAccessPathFileName()
    {
        foreach ($this->tests as $path => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($path));
            if($parts['is_file']){
                $uri = $uri->asFile();
            } else {
                $uri = $uri->asFolder();
            }
            $this->assertEquals($parts['access_path_file_name'], $uri->getAccessPathFileName());

            // Check if it returns null when asFile or asFolder is not called
            $p = Path::create(new AbsoluteReferencePathFormat($path));
            $this->assertNull($p->getAccessPathFileName());
        }
    }

    public function testCanParseAccessPathFileExtension()
    {
        foreach ($this->tests as $path => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($path));
            if($parts['is_file']){
                $uri = $uri->asFile();
            } else {
                $uri = $uri->asFolder();
            }
            $this->assertEquals($parts['access_path_file_extension'], $uri->getAccessPathFileExtension());

            // Check if it returns null when asFile or asFolder is not called
            $p = Path::create(new AbsoluteReferencePathFormat($path));
            $this->assertNull($p->getAccessPathFileExtension());
        }
    }

    public function testCanParseAccessPathRootFolder()
    {
        foreach ($this->tests as $uri => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            $this->assertEquals($parts['access_path_root_folder'], $uri->getAccessPathRootFolder());
        }
    }

    public function testCanParseAccessPath()
    {
        foreach ($this->tests as $uri => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            $this->assertEquals($parts['access_path'], $uri->getAccessPath());
        }
    }

    #######################
    #### REFERENCE PATH ###
    #######################

    public function testCanParseReferencePathFileName()
    {
        foreach ($this->tests as $path => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($path));
            if($parts['is_file']){
                $uri = $uri->asFile();
            } else {
                $uri = $uri->asFolder();
            }
            $this->assertEquals($parts['reference_path_file_name'], $uri->getReferencePathFileName());

            // Check if it returns null when asFile or asFolder is not called
            $p = Path::create(new AbsoluteReferencePathFormat($path));
            $this->assertNull($p->getReferencePathFileName());
        }
    }

    public function testCanParseReferencePathFileExtension()
    {
        foreach ($this->tests as $path => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($path));
            if($parts['is_file']){
                $uri = $uri->asFile();
            } else {
                $uri = $uri->asFolder();
            }
            $this->assertEquals($parts['reference_path_file_extension'], $uri->getReferencePathFileExtension());

            // Check if it returns null when asFile or asFolder is not called
            $p = Path::create(new AbsoluteReferencePathFormat($path));
            $this->assertNull($p->getReferencePathFileExtension());
        }
    }

    public function testCanParseReferencePathRootFolder()
    {
        foreach ($this->tests as $uri => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            $this->assertEquals($parts['reference_path_root_folder'], $uri->getReferencePathRootFolder());
        }
    }

    public function testCanParseReferencePath()
    {
        foreach ($this->tests as $uri => $parts) {
            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            $this->assertEquals($parts['reference_path'], $uri->getReferencePath());
        }
    }

    #######################################################
    #### SYSTEM PATH SPECIFIC (AbsoluteSystemPath.php) ####
    #######################################################

    public function testCanParseOSFamily()
    {
        

        foreach ($this->tests as $uri => $parts) {
            if(!isset($parts['operating_system'])){
                $this->assertTrue(true);
                continue;
            }

            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            $this->assertEquals($parts['operating_system'], $uri->getOSFamily());
        }
    }

    public function testCanParseLocationCategory()
    {
        foreach ($this->tests as $uri => $parts) {
            if(!isset($parts['location_category'])){
                $this->assertTrue(true);
                continue;
            }

            $uri = Path::create(new AbsoluteReferencePathFormat($uri));
            $this->assertEquals($parts['location_category'], $uri->getSystemPathLocationCategory());
        }
    }
}