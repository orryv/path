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

class WindowsPathTest extends PathsBase
{
    protected array $tests = [
        'C:/' => [
            'is_file' => false,
            'ds' => '\\',
            'operating_system' => OSFamily::WINDOWS,
            'location_category' => SystemPathLocationCategory::LOCAL,
            'NOTSCHEME' => 'HTTP', // different from scheme, to test it is different
            'scheme' => 'FILE',
            'host' => null,
            'path' => [],
            'access_uri_file_name' => null,
            'access_uri_file_extension' => null,
            'access_uri_root_folder' => 'file:///C:/',
            'access_uri' => 'file:///C:/',
            'access_path_file_name' => null,
            'access_path_file_extension' => null,
            'access_path_root_folder' => 'C:\\',
            'access_path' => 'C:\\',
            'reference_path_file_name' => null,
            'reference_path_file_extension' => null,
            'reference_path_root_folder' => 'C:/',
            'reference_path' => 'C:/',
        ],
        'C:/path/to/file.txt' => [
            'is_file' => true,
            'ds' => '\\',
            'operating_system' => OSFamily::WINDOWS,
            'location_category' => SystemPathLocationCategory::LOCAL,
            'NOTSCHEME' => 'HTTP', // different from scheme, to test it is different
            'scheme' => 'FILE',
            'host' => null,
            'path' => ['path', 'to', 'file.txt'],
            'access_uri_file_name' => 'file',
            'access_uri_file_extension' => 'txt',
            'access_uri_root_folder' => 'file:///C:/',
            'access_uri' => 'file:///C:/path/to/file.txt',
            'access_path_file_name' => 'file',
            'access_path_file_extension' => 'txt',
            'access_path_root_folder' => 'C:\\',
            'access_path' => 'C:\\path\\to\\file.txt',
            'reference_path_file_name' => 'file',
            'reference_path_file_extension' => 'txt',
            'reference_path_root_folder' => 'C:/',
            'reference_path' => 'C:/path/to/file.txt',
        ],
        // special characters
        'C:/path/to/file with@spaces_and#symbols?.txt' => [
            'is_file' => true,
            'ds' => '\\',
            'operating_system' => OSFamily::WINDOWS,
            'location_category' => SystemPathLocationCategory::LOCAL,
            'NOTSCHEME' => 'HTTP', // different from scheme, to test it is different
            'scheme' => 'FILE',
            'host' => null,
            'path' => ['path', 'to', 'file with@spaces_and#symbols?.txt'],
            'access_uri_file_name' => 'file%20with%40spaces_and%23symbols%3F',
            'access_uri_file_extension' => 'txt',
            'access_uri_root_folder' => 'file:///C:/',
            'access_uri' => 'file:///C:/path/to/file%20with%40spaces_and%23symbols%3F.txt',
            'access_path_file_name' => 'file with@spaces_and#symbols?',
            'access_path_file_extension' => 'txt',
            'access_path_root_folder' => 'C:\\',
            'access_path' => 'C:\\path\\to\\file with@spaces_and#symbols?.txt',
            'reference_path_file_name' => 'file with@spaces_and#symbols?',
            'reference_path_file_extension' => 'txt',
            'reference_path_root_folder' => 'C:/',
            'reference_path' => 'C:/path/to/file with@spaces_and#symbols?.txt',
        ],
        'file:///C:/path/to/file.txt' => [
            'is_file' => true,
            'ds' => '\\',
            'operating_system' => OSFamily::WINDOWS,
            'location_category' => SystemPathLocationCategory::LOCAL,
            'NOTSCHEME' => 'HTTP', // different from scheme, to test it is different
            'scheme' => 'FILE',
            'host' => null,
            'path' => ['path', 'to', 'file.txt'],
            'access_uri_file_name' => 'file',
            'access_uri_file_extension' => 'txt',
            'access_uri_root_folder' => 'file:///C:/',
            'access_uri' => 'file:///C:/path/to/file.txt',
            'access_path_file_name' => 'file',
            'access_path_file_extension' => 'txt',
            'access_path_root_folder' => 'C:\\',
            'access_path' => 'C:\\path\\to\\file.txt',
            'reference_path_file_name' => 'file',
            'reference_path_file_extension' => 'txt',
            'reference_path_root_folder' => 'C:/',
            'reference_path' => 'C:/path/to/file.txt',
        ],
        'C:\\access\\path\\file.txt' => [
            'is_file' => true,
            'ds' => '\\',
            'operating_system' => OSFamily::WINDOWS,
            'location_category' => SystemPathLocationCategory::LOCAL,
            'NOTSCHEME' => 'HTTP', // different from scheme, to test it is different
            'scheme' => 'FILE',
            'host' => null,
            'path' => ['access', 'path', 'file.txt'],
            'access_uri_file_name' => 'file',
            'access_uri_file_extension' => 'txt',
            'access_uri_root_folder' => 'file:///C:/',
            'access_uri' => 'file:///C:/access/path/file.txt',
            'access_path_file_name' => 'file',
            'access_path_file_extension' => 'txt',
            'access_path_root_folder' => 'C:\\',
            'access_path' => 'C:\\access\\path\\file.txt',
            'reference_path_file_name' => 'file',
            'reference_path_file_extension' => 'txt',
            'reference_path_root_folder' => 'C:/',
            'reference_path' => 'C:/access/path/file.txt',
        ],
    ];
}