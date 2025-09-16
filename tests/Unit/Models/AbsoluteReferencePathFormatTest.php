<?php

namespace Tests\Unit\Models;

use Orryv\Path\Models\AbsoluteReferencePathFormat;
use PHPUnit\Framework\TestCase;

class AbsoluteReferencePathFormatTest extends TestCase
{
    public function testConvertsWindowsBackslashesToForwardSlashes(): void
    {
        $format = new AbsoluteReferencePathFormat('C:\\Projects\\Demo\\file.txt');

        $this->assertSame('C:/Projects/Demo/file.txt', (string) $format);
    }

    public function testPreservesTrailingSlashWhenRequested(): void
    {
        $format = new AbsoluteReferencePathFormat('https://example.com/assets/', true);

        $this->assertSame('https://example.com/assets/', (string) $format);
    }

    public function testRemovesTrailingSlashWhenNotPreserving(): void
    {
        $format = new AbsoluteReferencePathFormat('https://example.com/assets/');

        $this->assertSame('https://example.com/assets', (string) $format);
    }

    public function testNormalizesUnixFileScheme(): void
    {
        $format = new AbsoluteReferencePathFormat('file:///var/log/syslog');

        $this->assertSame('/var/log/syslog', (string) $format);
    }

    public function testNormalizesGenericUri(): void
    {
        $format = new AbsoluteReferencePathFormat('git+ssh://example.com:2222/repo.git');

        $this->assertSame('git+ssh://example.com:2222/repo.git', (string) $format);
    }

    public function testRejectsRelativePath(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Input path not recognized');

        new AbsoluteReferencePathFormat('path/to/file.txt');
    }

    public function testWindowsNetworkPathRequiresShareSeparator(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid');

        new AbsoluteReferencePathFormat('file://server');
    }

    public function testExtractWindowsNetworkPathWithBackslashes(): void
    {
        $format = new AbsoluteReferencePathFormat('\\\\Server\\Share\\folder\\file.txt');

        [$prefix, $segments] = $format->extractWindowsNetworkPath('\\\\Server\\Share\\folder\\file.txt');

        $this->assertSame('//Server/', $prefix);
        $this->assertSame(['Share', 'folder', 'file.txt'], $segments);
    }
}

