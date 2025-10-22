<?php

namespace Tests\Integration;

use Orryv\Path;
use Orryv\Path\Enums\PathFormat;
use PHPUnit\Framework\TestCase;

class BaseDirectoryTest extends TestCase
{
    public function testWithBaseDirPreventsLeavingWindowsRoot(): void
    {
        $base = Path::dir('C:/Projects/App/', PathFormat::REFERENCE_PATH);
        $working = Path::dir('C:/Projects/App/storage/logs/', PathFormat::REFERENCE_PATH)->withBaseDir($base);

        $upOne = $working->cd('..');
        $this->assertSame('C:/Projects/App/storage/', $upOne->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('C:/Projects/App/storage/', $upOne->toString(PathFormat::ACCESS_PATH));

        $this->expectException(\OutOfBoundsException::class);
        $working->cd('../../..');
    }

    public function testWithBaseDirAllowsAbsoluteNavigationWithinBase(): void
    {
        $base = Path::dir('//server/share/', PathFormat::REFERENCE_PATH);
        $working = Path::dir('//server/share/exports/', PathFormat::REFERENCE_PATH)->withBaseDir($base);

        $archive = $working->cd('/archive/2023');

        $this->assertSame('//server/share/archive/2023/', $archive->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('\\\\server\\share\\archive\\2023\\', $archive->toString(PathFormat::ACCESS_PATH));
    }

    public function testWithBaseDirOnUrlKeepsOrigin(): void
    {
        $base = Path::url('https://example.com/app/', PathFormat::ACCESS_URI);
        $asset = Path::url('https://example.com/app/assets/images/logo.png', PathFormat::ACCESS_URI)->withBaseDir($base);

        $css = $asset->cd('../css/app.css');

        $this->assertSame('https://example.com/app/assets/css/app.css', $css->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('https://example.com/app/assets/css/app.css', $css->toString(PathFormat::ACCESS_URI));

        $this->expectException(\OutOfBoundsException::class);
        $asset->cd('https://cdn.example.com/logo.png');
    }
}

