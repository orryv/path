<?php

namespace Tests\Integration;

use Orryv\Path;
use Orryv\Path\Enums\PathFormat;
use PHPUnit\Framework\TestCase;

class UrlResolutionTest extends TestCase
{
    /**
     * @dataProvider hrefResolutionProvider
     */
    public function testCdResolvesHrefRelativeToBase(string $href, string $expectedReference, string $expectedAccessUri): void
    {
        $base = Path::url('https://example.com/a/b/c/index.html?x=1#frag', PathFormat::ACCESS_URI);

        $resolved = $base->cd($href);

        $this->assertSame($expectedReference, $resolved->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expectedAccessUri, $resolved->toString(PathFormat::ACCESS_URI));
    }

    public static function hrefResolutionProvider(): iterable
    {
        return [
            'empty href keeps document but drops fragment' => [
                '',
                'https://example.com/a/b/c/index.html?x=1',
                'https://example.com/a/b/c/index.html?x=1',
            ],
            'current directory' => [
                './',
                'https://example.com/a/b/c/',
                'https://example.com/a/b/c/',
            ],
            'parent directory' => [
                '..',
                'https://example.com/a/b/',
                'https://example.com/a/b/',
            ],
            'sibling directory file' => [
                '../d',
                'https://example.com/a/b/d',
                'https://example.com/a/b/d',
            ],
            'root relative' => [
                '/',
                'https://example.com/',
                'https://example.com/',
            ],
            'network path inherits scheme' => [
                '//cdn.example.com/x',
                'https://cdn.example.com/x',
                'https://cdn.example.com/x',
            ],
            'new query keeps path' => [
                '?y=2',
                'https://example.com/a/b/c/index.html?y=2',
                'https://example.com/a/b/c/index.html?y=2',
            ],
            'new fragment keeps query' => [
                '#top',
                'https://example.com/a/b/c/index.html?x=1#top',
                'https://example.com/a/b/c/index.html?x=1#top',
            ],
            'complex relative path with dot segments' => [
                './../shared/./images/logo.png',
                'https://example.com/a/shared/images/logo.png',
                'https://example.com/a/shared/images/logo.png',
            ],
            'absolute url replaces everything' => [
                'https://static.example.org/assets/app.css',
                'https://static.example.org/assets/app.css',
                'https://static.example.org/assets/app.css',
            ],
        ];
    }
}
