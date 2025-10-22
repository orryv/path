<?php

namespace Tests\Unit\Models;

use Orryv\Path\Models\AbsoluteAccessPathFormat;
use Orryv\Path\Models\AbsoluteAccessURIFormat;
use Orryv\Path\Exceptions\InvalidAbsoluteAccessPathFormatException;
use Orryv\Path\Exceptions\InvalidAbsoluteAccessURIFormatException;
use PHPUnit\Framework\TestCase;

class AccessFormatValidationTest extends TestCase
{
    public function testAcceptsValidAccessPath(): void
    {
        $format = new AbsoluteAccessPathFormat('C:\\Projects\\Demo\\file.txt');

        $this->assertSame('C:\\Projects\\Demo\\file.txt', (string) $format);
    }

    public function testRejectsRelativeAccessPath(): void
    {
        $this->expectException(InvalidAbsoluteAccessPathFormatException::class);
        $this->expectExceptionMessage('Invalid (absolute) AccessPath');

        new AbsoluteAccessPathFormat('projects/demo/file.txt');
    }

    public function testAcceptsValidAccessUri(): void
    {
        $format = new AbsoluteAccessURIFormat('https://example.com/path/to/file.txt');

        $this->assertSame('https://example.com/path/to/file.txt', (string) $format);
    }

    public function testRejectsUriWithSpaces(): void
    {
        $this->expectException(InvalidAbsoluteAccessURIFormatException::class);
        $this->expectExceptionMessage('Invalid (absolute) AccessURI');

        new AbsoluteAccessURIFormat('https://example.com/with space');
    }

    public function testRejectsUriWithBackslashes(): void
    {
        $this->expectException(InvalidAbsoluteAccessURIFormatException::class);

        new AbsoluteAccessURIFormat('https://example.com\\with-backslash');
    }
}

