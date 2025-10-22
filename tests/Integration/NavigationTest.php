<?php

namespace Tests\Integration;

use Orryv\Path;
use Orryv\Path\Enums\PathFormat;
use PHPUnit\Framework\TestCase;

class NavigationTest extends TestCase
{
    public function testCdWalksUpDirectories(): void
    {
        $file = Path::file('/var/www/html/index.php', PathFormat::ACCESS_PATH);
        $parent = $file->cd('..');

        $this->assertSame('/var/www/html/', $parent->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('/var/www/html/', $parent->toString(PathFormat::ACCESS_PATH));
    }

    public function testCdNavigatesIntoRelativeFolders(): void
    {
        $directory = Path::dir('/var/www/', PathFormat::ACCESS_PATH);
        $next = $directory->cd('app/cache');

        $this->assertSame('/var/www/app/cache/', $next->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('/var/www/app/cache/', $next->toString(PathFormat::ACCESS_PATH));
    }

    public function testCdHonorsBaseDirWhenResettingToRoot(): void
    {
        $base = Path::dir('/var/www/', PathFormat::REFERENCE_PATH);
        $file = Path::file('/var/www/html/index.php', PathFormat::REFERENCE_PATH)->withBaseDir($base);

        $rooted = $file->cd('/');

        $this->assertSame('/var/www/', $rooted->toString(PathFormat::REFERENCE_PATH));
    }

    public function testCdPreventsGoingAboveBase(): void
    {
        $base = Path::dir('/var/www/', PathFormat::REFERENCE_PATH);
        $file = Path::file('/var/www/html/index.php', PathFormat::REFERENCE_PATH)->withBaseDir($base);

        $this->expectException(\OutOfBoundsException::class);
        $file->cd('../../..');
    }

    public function testCdResolvesDotSegmentsWithinDirectory(): void
    {
        $directory = Path::dir('/srv/www/app/', PathFormat::REFERENCE_PATH);

        $resolved = $directory->cd('./storage/../logs/./today/');

        $this->assertSame('/srv/www/app/logs/today/', $resolved->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('/srv/www/app/logs/today/', $resolved->toString(PathFormat::ACCESS_PATH));
    }

    public function testCdWithinUncRootKeepsShare(): void
    {
        $unc = Path::dir('\\\\server\\share\\', PathFormat::ACCESS_PATH);

        $resolved = $unc->cd('dept/./reports/../reports/2023');

        $this->assertSame('//server/share/dept/reports/2023/', $resolved->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('\\\\server\\share\\dept\\reports\\2023\\', $resolved->toString(PathFormat::ACCESS_PATH));
    }

    public function testCommonBasePathAcrossUrls(): void
    {
        $base = Path::url('https://example.com/a/b/index.html', PathFormat::ACCESS_URI);
        $other = Path::url('https://example.com/a/c/app.js', PathFormat::ACCESS_URI);

        $common = $base->getCommonBasePath($other, PathFormat::REFERENCE_PATH);

        $this->assertSame('https://example.com/a/', $common->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('https://example.com/a/', $common->toString(PathFormat::ACCESS_PATH));
    }

    public function testCommonBasePathOnWindowsFileTree(): void
    {
        $left = Path::file('C:\\Projects\\App\\src\\main.php', PathFormat::ACCESS_PATH);
        $right = Path::file('C:\\Projects\\App\\tests\\Unit\\AppTest.php', PathFormat::ACCESS_PATH);

        $common = $left->getCommonBasePath($right, PathFormat::REFERENCE_PATH);

        $this->assertSame('C:/Projects/App/', $common->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('C:\\Projects\\App\\', $common->toString(PathFormat::ACCESS_PATH));
    }

    public function testCommonBasePathAcrossUncPaths(): void
    {
        $first = Path::file('\\\\server\\share\\dept\\reports\\Q1.pdf', PathFormat::ACCESS_PATH);
        $second = Path::dir('\\\\server\\share\\dept\\reports\\archive\\', PathFormat::ACCESS_PATH);

        $common = $first->getCommonBasePath($second, PathFormat::REFERENCE_PATH);

        $this->assertSame('//server/share/dept/reports/', $common->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('\\\\server\\share\\dept\\reports\\', $common->toString(PathFormat::ACCESS_PATH));
    }

    public function testPreserveEndSlashControlsDirectoryFormatting(): void
    {
        $directory = Path::dir('C:/Projects/Demo', PathFormat::REFERENCE_PATH);
        $withSlash = $directory->withPreserveEndSlash(true);
        $withoutSlash = $directory->withPreserveEndSlash(false);

        $this->assertSame('C:/Projects/Demo/', $withSlash->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('C:/Projects/Demo', $withoutSlash->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('C:\\Projects\\Demo\\', $withSlash->toString(PathFormat::ACCESS_PATH));
        $this->assertSame('C:\\Projects\\Demo', $withoutSlash->toString(PathFormat::ACCESS_PATH));
    }
}
