<?php

namespace Tests\Unit;

use Orryv\Path;
use Orryv\Path\Models\AbsoluteAccessPathFormat;
use PHPUnit\Framework\TestCase;

class AbsolutePathHelpersTest extends TestCase
{
    public function testSetBasePathRequiresKnownFolderOrFile(): void
    {
        $base = Path::create('/var/www/base');
        $path = Path::create('/var/www/base/index.php')->asFile();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown if $base_folder is a folder or a file');

        $path->setBasePath($base);
    }

    public function testGetNthFolderRejectsUnknownFormatClass(): void
    {
        $path = Path::create('/var/www/html/index.php')->asFile();

        $this->expectException(\InvalidArgumentException::class);
        $path->getNthFolder(0, \stdClass::class);
    }

    public function testPreserveEndSlashAffectsFolderHelpers(): void
    {
        $path = Path::create('C:/Projects/Demo/')->asFolder()->preserveEndSlash();

        $lastFolder = $path->getLastFolder(AbsoluteAccessPathFormat::class);

        $this->assertSame('C:\\Projects\\Demo\\', (string) $lastFolder);
        $this->assertSame('Demo', $path->getLastFolder());
    }

    public function testCdSupportsArrayCommands(): void
    {
        $path = Path::create('/var/www')->asFolder();

        $updated = $path->cd(['foo', '../bar', './baz'])->asFolder();

        $this->assertSame('/var/www/bar/baz', (string) $updated->getReferencePath());
        $this->assertSame(['var', 'www'], $path->getPath());
    }

    public function testAsDotTreatsParentSegmentAsFolder(): void
    {
        $path = Path::create('https://example.com/path/..')->asDot();

        $this->assertSame(['path', '..'], $path->getPath());
        $this->assertSame(['path', '..'], $path->getFolderPath());
    }
}

