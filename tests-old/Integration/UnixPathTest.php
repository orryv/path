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

class UnixPathTest extends PathsBase
{
    protected array $tests = [
        '/' => [
            'is_file' => false,
            'ds' => '/',
            'operating_system' => OSFamily::UNIX,
            'location_category' => SystemPathLocationCategory::LOCAL,
            'NOTSCHEME' => 'HTTP', // different from scheme, to test it is different
            'scheme' => 'FILE',
            'host' => null,
            'path' => [],
            'access_uri_file_name' => null,
            'access_uri_file_extension' => null,
            'access_uri_root_folder' => 'file:///',
            'access_uri' => 'file:///',
            'access_path_file_name' => null,
            'access_path_file_extension' => null,
            'access_path_root_folder' => '/',
            'access_path' => '/',
            'reference_path_file_name' => null,
            'reference_path_file_extension' => null,
            'reference_path_root_folder' => '/',
            'reference_path' => '/',
        ],
        '/path/to/file.txt' => [
            'is_file' => true,
            'ds' => '/',
            'operating_system' => OSFamily::UNIX,
            'location_category' => SystemPathLocationCategory::LOCAL,
            'NOTSCHEME' => 'HTTP', // different from scheme, to test it is different
            'scheme' => 'FILE',
            'host' => null,
            'path' => ['path', 'to', 'file.txt'],
            'access_uri_file_name' => 'file',
            'access_uri_file_extension' => 'txt',
            'access_uri_root_folder' => 'file:///',
            'access_uri' => 'file:///path/to/file.txt',
            'access_path_file_name' => 'file',
            'access_path_file_extension' => 'txt',
            'access_path_root_folder' => '/',
            'access_path' => '/path/to/file.txt',
            'reference_path_file_name' => 'file',
            'reference_path_file_extension' => 'txt',
            'reference_path_root_folder' => '/',
            'reference_path' => '/path/to/file.txt',
        ],
        '/path/to/special?chars#_@.txt' => [
            'is_file' => true,
            'ds' => '/',
            'operating_system' => OSFamily::UNIX,
            'location_category' => SystemPathLocationCategory::LOCAL,
            'NOTSCHEME' => 'HTTP', // different from scheme, to test it is different
            'scheme' => 'FILE',
            'host' => null,
            'path' => ['path', 'to', 'special?chars#_@.txt'],
            'access_uri_file_name' => 'special%3Fchars%23_%40',
            'access_uri_file_extension' => 'txt',
            'access_uri_root_folder' => 'file:///',
            'access_uri' => 'file:///path/to/special%3Fchars%23_%40.txt',
            'access_path_file_name' => 'special?chars#_@',
            'access_path_file_extension' => 'txt',
            'access_path_root_folder' => '/',
            'access_path' => '/path/to/special?chars#_@.txt',
            'reference_path_file_name' => 'special?chars#_@',
            'reference_path_file_extension' => 'txt',
            'reference_path_root_folder' => '/',
            'reference_path' => '/path/to/special?chars#_@.txt',
        ],
        // This will encode it twice... which is expected behavior
        'file:///path/to/special%3Fchars%23_%40.txt' => [
            'is_file' => true,
            'ds' => '/',
            'operating_system' => OSFamily::UNIX,
            'location_category' => SystemPathLocationCategory::LOCAL,
            'NOTSCHEME' => 'HTTP', // different from scheme, to test it is different
            'scheme' => 'FILE',
            'host' => null,
            'path' => ['path', 'to', 'special%3Fchars%23_%40.txt'],
            'access_uri_file_name' => 'special%253Fchars%2523_%2540',
            'access_uri_file_extension' => 'txt',
            'access_uri_root_folder' => 'file:///',
            'access_uri' => 'file:///path/to/special%253Fchars%2523_%2540.txt',
            'access_path_file_name' => 'special%3Fchars%23_%40',
            'access_path_file_extension' => 'txt',
            'access_path_root_folder' => '/',
            'access_path' => '/path/to/special%3Fchars%23_%40.txt',
            'reference_path_file_name' => 'special%3Fchars%23_%40',
            'reference_path_file_extension' => 'txt',
            'reference_path_root_folder' => '/',
            'reference_path' => '/path/to/special%3Fchars%23_%40.txt',
        ],
    ];

    public function testIsRightReturnType()
    {
        $uri = Path::create(new AbsoluteReferencePathFormat('/var/bin/file.txt'));
        $this->assertInstanceOf(AbsoluteUnixPath::class, $uri);
        
    }

    // #################
    // #### GENERAL ####
    // #################

    // public function testCanParseDS()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         $this->assertEquals($parts['ds'], $uri->ds());
    //     }
    // }

    // public function testCanParseScheme()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         $this->assertEquals($parts['scheme'], $uri->getScheme());
    //     }
    // }

    // public function testWithSchemeReturnsNewInstance()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         $new_uri = $uri->withScheme($parts['NOTSCHEME']);
    //         // assert that the original instance is not modified
    //         $this->assertEquals($parts['scheme'], $uri->getScheme());
    //         // assert that the new instance has the new scheme
    //         $this->assertEquals($parts['NOTSCHEME'], $new_uri->getScheme());
    //     }
    // }

    // public function testCanParseHost()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         $this->assertEquals($parts['host'], $uri->getHost());
    //     }
    // }

    // public function testWithHostReturnsNewInstance()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         $new_uri = $uri->withHost(['www', 'fgsgshghfgh', 'com']);
    //         // assert that the original instance is not modified
    //         $this->assertEquals(null, $uri->getHost());
    //         // assert that the new instance has the new host
    //         $this->assertEquals(['www', 'fgsgshghfgh', 'com'], $new_uri->getHost());
    //     }
    // }

    // public function testCanParseHostString()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         if($parts['host']){
    //             $this->assertEquals(implode('.', $parts['host']), $uri->getHostString());
    //         } else {
    //             $this->assertNull($uri->getHostString());
    //         }
    //     }
    // }

    // public function testWithHostStringReturnsNewInstance()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         $new_uri = $uri->withHostString('www.fgsgshghfgh.com');
    //         // assert that the original instance is not modified
    //         $this->assertEquals(null, $uri->getHost());
    //         // assert that the new instance has the new host
    //         $this->assertEquals(['www', 'fgsgshghfgh', 'com'], $new_uri->getHost());
    //     }
    // }

    // public function testCanParsePath()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         $this->assertEquals($parts['path'], $uri->getPath());
    //     }
    // }

    // public function testWithPathReturnsNewInstance()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         $original = $uri->getPath();
    //         $new_uri = $uri->withPath(['weird', 'fgsgshghfgh', 'file.txt']);
    //         // assert that the original instance is not modified
    //         $this->assertEquals($original, $uri->getPath());
    //         // assert that the new instance has the new path
    //         $this->assertEquals(['weird', 'fgsgshghfgh', 'file.txt'], $new_uri->getPath());
    //     }
    // }

    // ####################
    // #### ACCESS URI ####
    // ####################

    // public function testCanParseAccessURIFileName()
    // {
    //     foreach (self::TESTS as $path => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($path));
    //         if($parts['is_file']){
    //             $uri = $uri->asFile();
    //         } else {
    //             $uri = $uri->asFolder();
    //         }
    //         $this->assertEquals($parts['access_uri_file_name'], $uri->getAccessURIFileName());

    //         // Check if it returns null when asFile or asFolder is not called
    //         $p = Path::create(new AbsoluteReferencePathFormat($path));
    //         $this->assertNull($p->getAccessURIFileName());
    //     }
    // }

    // public function testCanParseAccessURIFileExtension()
    // {
    //     foreach (self::TESTS as $path => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($path));
    //         if($parts['is_file']){
    //             $uri = $uri->asFile();
    //         } else {
    //             $uri = $uri->asFolder();
    //         }
    //         $this->assertEquals($parts['access_uri_file_extension'], $uri->getAccessURIFileExtension());

    //         // Check if it returns null when asFile or asFolder is not called
    //         $p = Path::create(new AbsoluteReferencePathFormat($path));
    //         $this->assertNull($p->getAccessURIFileName());
    //     }
    // }

    // public function testCanParseAccessURIRootFolder()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         $this->assertEquals($parts['access_uri_root_folder'], $uri->getAccessURIRootFolder());
    //     }
    // }

    // public function testCanParseAccessURI()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         $this->assertEquals($parts['access_uri'], $uri->getAccessURI());
    //     }
    // }

    // #####################
    // #### ACCESS PATH ####
    // #####################

    // public function testCanParseAccessPathFileName()
    // {
    //     foreach (self::TESTS as $path => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($path));
    //         if($parts['is_file']){
    //             $uri = $uri->asFile();
    //         } else {
    //             $uri = $uri->asFolder();
    //         }
    //         $this->assertEquals($parts['access_path_file_name'], $uri->getAccessPathFileName());

    //         // Check if it returns null when asFile or asFolder is not called
    //         $p = Path::create(new AbsoluteReferencePathFormat($path));
    //         $this->assertNull($p->getAccessPathFileName());
    //     }
    // }

    // public function testCanParseAccessPathFileExtension()
    // {
    //     foreach (self::TESTS as $path => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($path));
    //         if($parts['is_file']){
    //             $uri = $uri->asFile();
    //         } else {
    //             $uri = $uri->asFolder();
    //         }
    //         $this->assertEquals($parts['access_path_file_extension'], $uri->getAccessPathFileExtension());

    //         // Check if it returns null when asFile or asFolder is not called
    //         $p = Path::create(new AbsoluteReferencePathFormat($path));
    //         $this->assertNull($p->getAccessPathFileExtension());
    //     }
    // }

    // public function testCanParseAccessPathRootFolder()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         $this->assertEquals($parts['access_path_root_folder'], $uri->getAccessPathRootFolder());
    //     }
    // }

    // public function testCanParseAccessPath()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         $this->assertEquals($parts['access_path'], $uri->getAccessPath());
    //     }
    // }

    // #######################
    // #### REFERENCE PATH ###
    // #######################

    // public function testCanParseReferencePathFileName()
    // {
    //     foreach (self::TESTS as $path => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($path));
    //         if($parts['is_file']){
    //             $uri = $uri->asFile();
    //         } else {
    //             $uri = $uri->asFolder();
    //         }
    //         $this->assertEquals($parts['reference_path_file_name'], $uri->getReferencePathFileName());

    //         // Check if it returns null when asFile or asFolder is not called
    //         $p = Path::create(new AbsoluteReferencePathFormat($path));
    //         $this->assertNull($p->getReferencePathFileName());
    //     }
    // }

    // public function testCanParseReferencePathFileExtension()
    // {
    //     foreach (self::TESTS as $path => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($path));
    //         if($parts['is_file']){
    //             $uri = $uri->asFile();
    //         } else {
    //             $uri = $uri->asFolder();
    //         }
    //         $this->assertEquals($parts['reference_path_file_extension'], $uri->getReferencePathFileExtension());

    //         // Check if it returns null when asFile or asFolder is not called
    //         $p = Path::create(new AbsoluteReferencePathFormat($path));
    //         $this->assertNull($p->getReferencePathFileExtension());
    //     }
    // }

    // public function testCanParseReferencePathRootFolder()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         $this->assertEquals($parts['reference_path_root_folder'], $uri->getReferencePathRootFolder());
    //     }
    // }

    // public function testCanParseReferencePath()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         $this->assertEquals($parts['reference_path'], $uri->getReferencePath());
    //     }
    // }

    // #######################################################
    // #### SYSTEM PATH SPECIFIC (AbsoluteSystemPath.php) ####
    // #######################################################

    // public function testCanParseOSFamily()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         $this->assertEquals($parts['operating_system'], $uri->getOSFamily());
    //     }
    // }

    // public function testCanParseLocationCategory()
    // {
    //     foreach (self::TESTS as $uri => $parts) {
    //         $uri = Path::create(new AbsoluteReferencePathFormat($uri));
    //         $this->assertEquals($parts['location_category'], $uri->getSystemPathLocationCategory());
    //     }
    // }

    



}