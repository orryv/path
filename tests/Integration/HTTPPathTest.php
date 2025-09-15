<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Orryv\Path;
use Orryv\Path\Exceptions\UnknownIfFolderOrFileException;
use Orryv\Path\Exceptions\AboveBaseFolderException;
use Orryv\Path\Paths\AbsoluteUnixPath;
use Orryv\Path\Enums\SystemPathLocationCategory;
use Orryv\Path\Enums\Encoder;
use Orryv\Path\Models\AbsoluteReferencePathFormat;

class HTTPPathTest extends PathsBase
{
    protected array $tests = [
        'http://www.text.com' => [
            'is_file' => false,
            'ds' => '/',
            'NOTSCHEME' => 'FILE', // different from scheme, to test it is different
            'scheme' => 'HTTP',
            'host' => ['www', 'text', 'com'],
            'path' => [],
            'query' => null,
            'fragment' => null,
            'access_uri_file_name' => null,
            'access_uri_file_extension' => null,
            'access_uri_root_folder' => 'http://www.text.com/',
            'access_uri' => 'http://www.text.com',
            'access_path_file_name' => null,
            'access_path_file_extension' => null,
            'access_path_root_folder' => 'http://www.text.com/',
            'access_path' => 'http://www.text.com',
            'reference_path_file_name' => null,
            'reference_path_file_extension' => null,
            'reference_path_root_folder' => 'http://www.text.com/',
            'reference_path' => 'http://www.text.com',
        ],
        'http://www.text.com/path/to/file.txt' => [
            'is_file' => true,
            'ds' => '/',
            'NOTSCHEME' => 'FILE', // different from scheme, to test it is different
            'scheme' => 'HTTP',
            'host' => ['www', 'text', 'com'],
            'path' => ['path', 'to', 'file.txt'],
            'query' => null,
            'fragment' => null,
            'access_uri_file_name' => 'file',
            'access_uri_file_extension' => 'txt',
            'access_uri_root_folder' => 'http://www.text.com/',
            'access_uri' => 'http://www.text.com/path/to/file.txt',
            'access_path_file_name' => 'file',
            'access_path_file_extension' => 'txt',
            'access_path_root_folder' => 'http://www.text.com/',
            'access_path' => 'http://www.text.com/path/to/file.txt',
            'reference_path_file_name' => 'file',
            'reference_path_file_extension' => 'txt',
            'reference_path_root_folder' => 'http://www.text.com/',
            'reference_path' => 'http://www.text.com/path/to/file.txt',
        ],
        // with query and fragment
        'http://www.text.com/path/to/file.txt?query=1&novalue#fragment' => [
            'is_file' => true,
            'ds' => '/',
            'NOTSCHEME' => 'FILE', // different from scheme, to test it is different
            'scheme' => 'HTTP',
            'host' => ['www', 'text', 'com'],
            'path' => ['path', 'to', 'file.txt'],
            'query' => ['query' => '1', 'novalue' => null],
            'fragment' => 'fragment',
            'access_uri_file_name' => 'file',
            'access_uri_file_extension' => 'txt',
            'access_uri_root_folder' => 'http://www.text.com/',
            'access_uri' => 'http://www.text.com/path/to/file.txt?query=1&novalue#fragment',
            'access_path_file_name' => 'file',
            'access_path_file_extension' => 'txt',
            'access_path_root_folder' => 'http://www.text.com/',
            'access_path' => 'http://www.text.com/path/to/file.txt?query=1&novalue#fragment',
            'reference_path_file_name' => 'file',
            'reference_path_file_extension' => 'txt',
            'reference_path_root_folder' => 'http://www.text.com/',
            'reference_path' => 'http://www.text.com/path/to/file.txt?query=1&novalue#fragment',
        ],
        'sftp://username:password@hostname:22/path/to/file.txt' => [
            'is_file' => true,
            'ds' => '/',
            'NOTSCHEME' => 'FILE', // different from scheme, to test it is different
            'scheme' => 'SFTP',
            'host' => ['hostname'],
            'path' => ['path', 'to', 'file.txt'],
            'query' => null,
            'fragment' => null,
            'username' => 'username',
            'password' => 'password',
            'port' => 22,
            'access_uri_file_name' => 'file',
            'access_uri_file_extension' => 'txt',
            'access_uri_root_folder' => 'sftp://username:password@hostname:22/',
            'access_uri' => 'sftp://username:password@hostname:22/path/to/file.txt',
            'access_path_file_name' => 'file',
            'access_path_file_extension' => 'txt',
            'access_path_root_folder' => 'sftp://username:password@hostname:22/',
            'access_path' => 'sftp://username:password@hostname:22/path/to/file.txt',
            'reference_path_file_name' => 'file',
            'reference_path_file_extension' => 'txt',
            'reference_path_root_folder' => 'sftp://username:password@hostname:22/',
            'reference_path' => 'sftp://username:password@hostname:22/path/to/file.txt',
        ],
    ];

    public function testDecodingIsDisabled()
    {
        $uri = Path::create(new AbsoluteReferencePathFormat('http://www.text.com/path/to/file@.txt'));
        $this->assertEquals('http://www.text.com/path/to/file@.txt', $uri->getReferencePath());
    }

    public function testEncoding()
    {
        $uri = Path::create(new AbsoluteReferencePathFormat('http://www.text.com/path/to/file%2Bsometign%25.txt?query=1&novalue#fragment'))
            ->withEncoding(Encoder::URLENCODE);

        $expects = 'http://www.text.com/path/to/file+sometign%.txt?query=1&novalue#fragment';
        $this->assertEquals($expects, (string) $uri->getReferencePath());
    }

    public function testUsernameParsedCorrectly()
    {
        $uri = Path::create(new AbsoluteReferencePathFormat('http://username@www.text.com/path/to/file'));
        $this->assertEquals('username', $uri->getUsername());
    }

    public function testWithUsername()
    {
        $uri = Path::create(new AbsoluteReferencePathFormat('http://www.huh.com/path/to/file'))
            ->withUsername('username');

        $this->assertEquals('http://username@www.huh.com/path/to/file', (string) $uri->getReferencePath());
    }

    public function testUsernameAndPasswordParsedCorrectly()
    {
        $uri = Path::create(new AbsoluteReferencePathFormat('http://username:password@www.text.com/path/to/file'));
        $this->assertEquals('username', $uri->getUsername());
        $this->assertEquals('password', $uri->getPassword());
    }

    public function testWithPassword()
    {
        $uri = Path::create(new AbsoluteReferencePathFormat('http://username:oldpassword@www.huh.com/path/to/file'))
            ->withPassword('password');

        $this->assertEquals('http://username:password@www.huh.com/path/to/file', (string) $uri->getReferencePath());
    }

    public function testHTTP()
    {
        $url = Path::create('https://website.com/path/to/page?query=string#fragment') // current page
            ->asFile() // set current page as a file
            ->rmQuery() // remove query from the url
            ->rmFragment() // remove fragment from the url
            ->cd('path/to/another/file.jpg'); // href

        $this->assertEquals('https://website.com/path/to/path/to/another/file.jpg', (string) $url->getReferencePath());
    }

    public function testHTTP2()
    {
        // move to base folder instead of current folder
        $url = Path::create('https://website.com/path/to/page?query=string#fragment') // current page
            ->asFile() // set current page as a file
            ->rmQuery() // remove query from the url
            ->rmFragment() // remove fragment from the url
            ->cd('/path/to/another/file.jpg'); // href

        $this->assertEquals('https://website.com/path/to/another/file.jpg', (string) $url->getReferencePath());
    }
}