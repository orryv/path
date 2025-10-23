<?php

namespace Tests\Integration;

use InvalidArgumentException;
use Orryv\Path;
use Orryv\Path\Enums\PathFormat;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

class UrlPathAdvancedIntegrationTest extends TestCase
{
    /**
     * @dataProvider relativeHrefProvider
     */
    public function testCdResolvesRelativeHrefs(string $relative, string $expectedReference, string $expectedAccess): void
    {
        $url = Path::url('https://example.com/app/assets/images/logo.png?version=1#top', PathFormat::REFERENCE_PATH);

        $result = $url->cd($relative);

        $this->assertSame($expectedReference, $result->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expectedAccess, $result->toString(PathFormat::ACCESS_URI));
    }

    public static function relativeHrefProvider(): iterable
    {
        yield 'navigate to sibling css asset with query and fragment' => [
            '../css/app.css?version=2#header',
            'https://example.com/app/assets/css/app.css?version=2#header',
            'https://example.com/app/assets/css/app.css?version=2#header',
        ];
        yield 'move up two levels before descending' => [
            '../../scripts/app.js',
            'https://example.com/app/scripts/app.js',
            'https://example.com/app/scripts/app.js',
        ];
        yield 'preserve spaces while encoding access uri' => [
            '../fonts/Open Sans.woff2',
            'https://example.com/app/assets/fonts/Open Sans.woff2',
            'https://example.com/app/assets/fonts/Open%20Sans.woff2',
        ];
    }

    public function testCdWithQueryStringReplacesQueryAndClearsFragment(): void
    {
        $url = Path::url('https://example.com/app/assets/images/logo.png?version=1#top', PathFormat::REFERENCE_PATH);

        $updated = $url->cd('?version=3');

        $this->assertSame('https://example.com/app/assets/images/logo.png?version=3', $updated->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('https://example.com/app/assets/images/logo.png?version=3', $updated->toString(PathFormat::ACCESS_URI));
    }

    public function testCdWithFragmentOnlyUpdatesFragment(): void
    {
        $url = Path::url('https://example.com/app/assets/images/logo.png?version=1', PathFormat::REFERENCE_PATH);

        $updated = $url->cd('#footer');

        $this->assertSame('https://example.com/app/assets/images/logo.png?version=1#footer', $updated->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('https://example.com/app/assets/images/logo.png?version=1#footer', $updated->toString(PathFormat::ACCESS_URI));
    }

    public function testCdWithEmptyHrefRemovesFragment(): void
    {
        $url = Path::url('https://example.com/app/assets/images/logo.png?version=1#top', PathFormat::REFERENCE_PATH);

        $updated = $url->cd('');

        $this->assertSame('https://example.com/app/assets/images/logo.png?version=1', $updated->toString(PathFormat::REFERENCE_PATH));
    }

    public function testWithBaseDirRejectsPathWithoutTrailingSlash(): void
    {
        $url = Path::url('https://example.com/app/assets/css/app.css', PathFormat::REFERENCE_PATH);

        $this->expectException(InvalidArgumentException::class);
        $url->withBaseDir('https://example.com/app');
    }

    public function testWithBaseDirPreventsEscapingOrigin(): void
    {
        $base = Path::url('https://example.com/app/', PathFormat::REFERENCE_PATH);
        $asset = Path::url('https://example.com/app/assets/css/app.css', PathFormat::REFERENCE_PATH)->withBaseDir($base);

        $this->expectException(OutOfBoundsException::class);
        $asset->cd('https://cdn.example.com/app.css');
    }

    public function testWithQueryArrayProducesExpectedStrings(): void
    {
        $url = Path::url('https://example.com/app/report', PathFormat::REFERENCE_PATH);

        $withQuery = $url->withQuery([
            'filter' => 'new',
            'tags' => ['php', 'path'],
        ]);

        $this->assertSame('https://example.com/app/report?filter=new&tags[0]=php&tags[1]=path', $withQuery->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('https://example.com/app/report?filter=new&tags%5B0%5D=php&tags%5B1%5D=path', $withQuery->toString(PathFormat::ACCESS_URI));
    }

    public function testWithoutQueryRemovesSpecificKeys(): void
    {
        $url = Path::url('https://example.com/app/report?filter=new&tags%5B0%5D=php&tags%5B1%5D=path', PathFormat::ACCESS_URI);

        $updated = $url->withoutQuery(['filter']);

        $this->assertSame('https://example.com/app/report?tags[0]=php&tags[1]=path', $updated->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('https://example.com/app/report?tags%5B0%5D=php&tags%5B1%5D=path', $updated->toString(PathFormat::ACCESS_URI));
    }

    public function testWithoutQueryClearsAllWhenNull(): void
    {
        $url = Path::url('https://example.com/app/report?filter=new', PathFormat::REFERENCE_PATH);

        $cleared = $url->withoutQuery();

        $this->assertSame('https://example.com/app/report', $cleared->toString(PathFormat::REFERENCE_PATH));
    }

    /**
     * @dataProvider urlRelativeProvider
     */
    public function testGetRelativePathBetweenUrls(string $baseUrl, string $targetUrl, string $expectedReference, string $expectedAccess): void
    {
        $base = Path::url($baseUrl, PathFormat::REFERENCE_PATH);
        $target = Path::url($targetUrl, PathFormat::REFERENCE_PATH);

        $this->assertSame($expectedReference, $base->getRelativePathTo($target, PathFormat::REFERENCE_PATH));
        $this->assertSame($expectedReference, $target->getRelativePathFrom($base, PathFormat::REFERENCE_PATH));
        $this->assertSame($expectedAccess, $base->getRelativePathTo($target, PathFormat::ACCESS_URI));
        $this->assertSame($expectedAccess, $target->getRelativePathFrom($base, PathFormat::ACCESS_URI));
    }

    public static function urlRelativeProvider(): iterable
    {
        yield 'base directory to nested asset' => [
            'https://example.com/app/',
            'https://example.com/app/assets/css/app.css',
            'assets/css/app.css',
            'assets/css/app.css',
        ];
        yield 'dashboard to sibling asset' => [
            'https://example.com/app/dashboard/',
            'https://example.com/app/assets/css/app.css',
            '../assets/css/app.css',
            '../assets/css/app.css',
        ];
        yield 'encode spaces for access uri relative paths' => [
            'https://example.com/app/assets/images/',
            'https://example.com/app/assets/fonts/Open Sans.woff2',
            '../fonts/Open Sans.woff2',
            '../fonts/Open%20Sans.woff2',
        ];
    }

    /**
     * @dataProvider commonUrlBaseProvider
     */
    public function testGetCommonBasePathForUrls(string $first, string $second, string $expectedReference): void
    {
        $a = Path::url($first, PathFormat::REFERENCE_PATH);
        $b = Path::url($second, PathFormat::REFERENCE_PATH);

        $common = $a->getCommonBasePath($b, PathFormat::REFERENCE_PATH);

        $this->assertSame($expectedReference, $common->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expectedReference, $common->toString(PathFormat::ACCESS_URI));
    }

    public static function commonUrlBaseProvider(): iterable
    {
        yield 'shared application root' => [
            'https://example.com/app/assets/css/app.css',
            'https://example.com/app/api/v1/index.json',
            'https://example.com/app/',
        ];
        yield 'shared releases directory' => [
            'https://example.com/app/releases/2024/index.html',
            'https://example.com/app/releases/2023/notes.html',
            'https://example.com/app/releases/',
        ];
    }
}
