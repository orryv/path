<?php

namespace Tests\Unit;

use Orryv\Path;
use Orryv\Path\Exceptions\InvalidAbsoluteReferencePathFormatException;
use Orryv\Path\Models\AbsoluteReferencePathFormat;
use Orryv\Path\Paths\AbsoluteURIPath;
use Orryv\Path\Paths\AbsoluteUnixPath;
use Orryv\Path\Paths\AbsoluteWindowsNetworkPath;
use Orryv\Path\Paths\AbsoluteWindowsPath;
use PHPUnit\Framework\TestCase;

class PathFactoryTest extends TestCase
{
    public function testCreateDetectsHttpUri(): void
    {
        $path = Path::create('https://example.com/resource');

        $this->assertInstanceOf(AbsoluteURIPath::class, $path);
        $this->assertSame('https://example.com/resource', (string) $path->getReferencePath());
    }

    public function testCreateDetectsGenericUri(): void
    {
        $path = Path::create('ftp://files.example.com/archive.tar.gz');

        $this->assertInstanceOf(AbsoluteURIPath::class, $path);
        $this->assertSame('ftp://files.example.com/archive.tar.gz', (string) $path->getReferencePath());
    }

    public function testCreateDetectsWindowsDrivePath(): void
    {
        $path = Path::create('C:/Windows/System32');

        $this->assertInstanceOf(AbsoluteWindowsPath::class, $path);
        $this->assertSame('C:/Windows/System32', (string) $path->getReferencePath());
    }

    public function testCreateDetectsWindowsNetworkPath(): void
    {
        $path = Path::create('\\\\Server01\\Share\\Folder');

        $this->assertInstanceOf(AbsoluteWindowsNetworkPath::class, $path);
        $this->assertSame('file://Server01/Share/Folder', (string) $path->getAccessURI());
    }

    public function testCreateDetectsUnixPath(): void
    {
        $path = Path::create('/var/log/nginx');

        $this->assertInstanceOf(AbsoluteUnixPath::class, $path);
        $this->assertSame('/var/log/nginx', (string) $path->getReferencePath());
    }

    public function testCreateTrimsWhitespace(): void
    {
        $path = Path::create('   /opt/app/config  ')->asFolder();

        $this->assertSame('/opt/app/config', (string) $path->getReferencePath());
    }

    public function testCreateRejectsEmptyString(): void
    {
        $this->expectException(InvalidAbsoluteReferencePathFormatException::class);
        $this->expectExceptionMessage('Invalid Path: Empty path');

        Path::create('    ');
    }

    public function testCreateRejectsInvalidUtf8(): void
    {
        $invalid = "https://example.com/resource" . "\xC3\x28";

        $this->expectException(InvalidAbsoluteReferencePathFormatException::class);
        $this->expectExceptionMessage('Invalid Path: The path is not a valid UTF-8 string');

        Path::create($invalid);
    }

    public function testHttpUriDetectionHelpers(): void
    {
        $http = new AbsoluteReferencePathFormat('https://example.com/path');
        $generic = new AbsoluteReferencePathFormat('git+ssh://example.com/repo.git');

        $this->assertTrue(Path::isHTTPURI($http));
        $this->assertFalse(Path::isHTTPURI($generic));
    }

    public function testGenericUriDetectionHelperExcludesHttp(): void
    {
        $http = new AbsoluteReferencePathFormat('http://example.com');
        $generic = new AbsoluteReferencePathFormat('s3://bucket/object');

        $this->assertFalse(Path::isGenericURI($http));
        $this->assertTrue(Path::isGenericURI($generic));
    }

    public function testWindowsPathDetectionHelper(): void
    {
        $windows = new AbsoluteReferencePathFormat('C:/Program Files');
        $unix = new AbsoluteReferencePathFormat('/var/log');

        $this->assertTrue(Path::isWindowsPath($windows));
        $this->assertFalse(Path::isWindowsPath($unix));
    }

    public function testWindowsNetworkPathDetectionSupportsUnicodeHosts(): void
    {
        $network = new AbsoluteReferencePathFormat('//Tésté/Share/Folder');

        $this->assertTrue(Path::isWindowsNetworkPath($network));
    }

    public function testUnixPathDetectionRejectsNetworkShares(): void
    {
        $network = new AbsoluteReferencePathFormat('//server/share');
        $unix = new AbsoluteReferencePathFormat('/usr/bin');

        $this->assertFalse(Path::isUnixPath($network));
        $this->assertTrue(Path::isUnixPath($unix));
    }
}

