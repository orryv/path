<?php

namespace Tests\Unit;

use Orryv\Path;
use Orryv\Path\Enums\PathFormat;
use Orryv\Path\Exceptions\DifferentOriginException;
use PHPUnit\Framework\TestCase;

class RelativeOperationsTest extends TestCase
{
    public function testRelativePathFromDifferentDriveThrows(): void
    {
        $base = Path::dir('C:/projects/demo/', PathFormat::ACCESS_PATH);
        $target = Path::file('D:/projects/demo/app.php', PathFormat::ACCESS_PATH);

        $this->expectException(DifferentOriginException::class);
        $target->getRelativePathFrom($base);
    }

    public function testRelativePathToDifferentUncShareThrows(): void
    {
        $base = Path::dir('\\\\server\\share1\\', PathFormat::ACCESS_PATH);
        $target = Path::file('\\\\server\\share2\\folder\\file.txt', PathFormat::ACCESS_PATH);

        $this->expectException(DifferentOriginException::class);
        $base->getRelativePathTo($target);
    }

    public function testRelativePathBetweenDifferentHostsThrows(): void
    {
        $base = Path::url('https://example.com/a/b/index.html', PathFormat::ACCESS_URI);
        $target = Path::url('https://cdn.example.com/a/b/app.js', PathFormat::ACCESS_URI);

        $this->expectException(DifferentOriginException::class);
        $base->getRelativePathTo($target);
    }

    public function testRelativePathBetweenFilesWithinSameTree(): void
    {
        $base = Path::file('/var/www/html/index.php', PathFormat::REFERENCE_PATH);
        $target = Path::file('/var/www/assets/images/logo.png', PathFormat::REFERENCE_PATH);

        $this->assertSame('../assets/images/logo.png', $base->getRelativePathTo($target, PathFormat::REFERENCE_PATH));
        $this->assertSame('../assets/images/logo.png', $base->getRelativePathTo($target, PathFormat::ACCESS_PATH));
    }

    public function testRelativePathFromDirectoryToNestedFile(): void
    {
        $base = Path::dir('C:/Projects/App/', PathFormat::REFERENCE_PATH);
        $target = Path::file('C:/Projects/App/src/Controller/Home.php', PathFormat::REFERENCE_PATH);

        $this->assertSame('src/Controller/Home.php', $target->getRelativePathFrom($base, PathFormat::REFERENCE_PATH));
        $this->assertSame('src/Controller/Home.php', $target->getRelativePathFrom($base, PathFormat::ACCESS_PATH));
    }

    public function testRelativePathBetweenUrlsWithSharedOrigin(): void
    {
        $base = Path::url('https://example.com/app/docs/intro.html', PathFormat::ACCESS_URI);
        $target = Path::url('https://example.com/app/assets/css/app.css', PathFormat::ACCESS_URI);

        $this->assertSame('../assets/css/app.css', $base->getRelativePathTo($target, PathFormat::REFERENCE_PATH));
        $this->assertSame('../assets/css/app.css', $base->getRelativePathTo($target, PathFormat::ACCESS_PATH));
    }

    public function testRelativePathAcrossUncSubfolders(): void
    {
        $base = Path::dir('\\\\server\\share\\reports\\', PathFormat::ACCESS_PATH);
        $target = Path::file('\\\\server\\share\\reports\\2023\\Q1.xlsx', PathFormat::ACCESS_PATH);

        $this->assertSame('2023\\Q1.xlsx', $target->getRelativePathFrom($base, PathFormat::ACCESS_PATH));
        $this->assertSame('2023/Q1.xlsx', $target->getRelativePathFrom($base, PathFormat::REFERENCE_PATH));
    }
}
