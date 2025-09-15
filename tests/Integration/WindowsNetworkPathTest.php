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

class WindowsNetworkPathTest extends PathsBase
{
    protected array $tests = [
        '//NAS/' => [
            'is_file' => false,
            'ds' => '\\',
            'operating_system' => OSFamily::WINDOWS,
            'location_category' => SystemPathLocationCategory::NETWORK,
            'NOTSCHEME' => 'HTTP', // different from scheme, to test it is different
            'scheme' => 'FILE',
            'host' => ['NAS'],
            'path' => [],
            'access_uri_file_name' => null,
            'access_uri_file_extension' => null,
            'access_uri_root_folder' => 'file://NAS/',
            'access_uri' => 'file://NAS/',
            'access_path_file_name' => null,
            'access_path_file_extension' => null,
            'access_path_root_folder' => '\\\\NAS\\',
            'access_path' => '\\\\NAS\\',
            'reference_path_file_name' => null,
            'reference_path_file_extension' => null,
            'reference_path_root_folder' => '//NAS/',
            'reference_path' => '//NAS/',
        ],
        '//NAS/path/to/file.txt' => [
            'is_file' => true,
            'ds' => '\\',
            'operating_system' => OSFamily::WINDOWS,
            'location_category' => SystemPathLocationCategory::NETWORK,
            'NOTSCHEME' => 'HTTP', // different from scheme, to test it is different
            'scheme' => 'FILE',
            'host' => ['NAS'],
            'path' => ['path', 'to', 'file.txt'],
            'access_uri_file_name' => 'file',
            'access_uri_file_extension' => 'txt',
            'access_uri_root_folder' => 'file://NAS/',
            'access_uri' => 'file://NAS/path/to/file.txt',
            'access_path_file_name' => 'file',
            'access_path_file_extension' => 'txt',
            'access_path_root_folder' => '\\\\NAS\\',
            'access_path' => '\\\\NAS\\path\\to\\file.txt',
            'reference_path_file_name' => 'file',
            'reference_path_file_extension' => 'txt',
            'reference_path_root_folder' => '//NAS/',
            'reference_path' => '//NAS/path/to/file.txt',
        ],
        '//NAS/Wi th/Spec#ial/cg@r#s?.txt' => [
            'is_file' => true,
            'ds' => '\\',
            'operating_system' => OSFamily::WINDOWS,
            'location_category' => SystemPathLocationCategory::NETWORK,
            'NOTSCHEME' => 'HTTP', // different from scheme, to test it is different
            'scheme' => 'FILE',
            'host' => ['NAS'],
            'path' => ['Wi th', 'Spec#ial', 'cg@r#s?.txt'],
            'access_uri_file_name' => 'cg%40r%23s%3F',
            'access_uri_file_extension' => 'txt',
            'access_uri_root_folder' => 'file://NAS/',
            'access_uri' => 'file://NAS/Wi%20th/Spec%23ial/cg%40r%23s%3F.txt',
            'access_path_file_name' => 'cg@r#s?',
            'access_path_file_extension' => 'txt',
            'access_path_root_folder' => '\\\\NAS\\',
            'access_path' => '\\\\NAS\\Wi th\\Spec#ial\\cg@r#s?.txt',
            'reference_path_file_name' => 'cg@r#s?',
            'reference_path_file_extension' => 'txt',
            'reference_path_root_folder' => '//NAS/',
            'reference_path' => '//NAS/Wi th/Spec#ial/cg@r#s?.txt',
        ],
        '\\\\NAS\\path\\to\\file.txt' => [
            'is_file' => true,
            'ds' => '\\',
            'operating_system' => OSFamily::WINDOWS,
            'location_category' => SystemPathLocationCategory::NETWORK,
            'NOTSCHEME' => 'HTTP', // different from scheme, to test it is different
            'scheme' => 'FILE',
            'host' => ['NAS'],
            'path' => ['path', 'to', 'file.txt'],
            'access_uri_file_name' => 'file',
            'access_uri_file_extension' => 'txt',
            'access_uri_root_folder' => 'file://NAS/',
            'access_uri' => 'file://NAS/path/to/file.txt',
            'access_path_file_name' => 'file',
            'access_path_file_extension' => 'txt',
            'access_path_root_folder' => '\\\\NAS\\',
            'access_path' => '\\\\NAS\\path\\to\\file.txt',
            'reference_path_file_name' => 'file',
            'reference_path_file_extension' => 'txt',
            'reference_path_root_folder' => '//NAS/',
            'reference_path' => '//NAS/path/to/file.txt',
        ],
        'file://NAS/path/to/file.txt' => [
            'is_file' => true,
            'ds' => '\\',
            'operating_system' => OSFamily::WINDOWS,
            'location_category' => SystemPathLocationCategory::NETWORK,
            'NOTSCHEME' => 'HTTP', // different from scheme, to test it is different
            'scheme' => 'FILE',
            'host' => ['NAS'],
            'path' => ['path', 'to', 'file.txt'],
            'access_uri_file_name' => 'file',
            'access_uri_file_extension' => 'txt',
            'access_uri_root_folder' => 'file://NAS/',
            'access_uri' => 'file://NAS/path/to/file.txt',
            'access_path_file_name' => 'file',
            'access_path_file_extension' => 'txt',
            'access_path_root_folder' => '\\\\NAS\\',
            'access_path' => '\\\\NAS\\path\\to\\file.txt',
            'reference_path_file_name' => 'file',
            'reference_path_file_extension' => 'txt',
            'reference_path_root_folder' => '//NAS/',
            'reference_path' => '//NAS/path/to/file.txt',
        ],
    ];
}