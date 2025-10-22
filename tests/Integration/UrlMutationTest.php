<?php

namespace Tests\Integration;

use Orryv\Path;
use Orryv\Path\Enums\PathFormat;
use Orryv\Path\Exceptions\DifferentOriginException;
use Orryv\Path\UrlPath;
use PHPUnit\Framework\TestCase;

class UrlMutationTest extends TestCase
{
    /**
     * @dataProvider withQueryProvider
     */
    public function testWithQueryVariants(string $input, PathFormat $format, array|string|null $query, array $expected): void
    {
        $url = Path::url($input, $format);

        $updated = $url->withQuery($query);

        $this->assertInstanceOf(UrlPath::class, $updated);
        $this->assertSame($expected['reference'], $updated->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expected['access'], $updated->toString(PathFormat::ACCESS_PATH));
        $this->assertSame($expected['access'], $updated->toString(PathFormat::ACCESS_URI));
    }

    public static function withQueryProvider(): iterable
    {
        return [
            'array query builds nested structure' => [
                'https://example.com/app/index.html',
                PathFormat::REFERENCE_PATH,
                ['page' => 1, 'filter' => ['type' => 'image']],
                [
                    'reference' => 'https://example.com/app/index.html?page=1&filter[type]=image',
                    'access' => 'https://example.com/app/index.html?page=1&filter%5Btype%5D=image',
                ],
            ],
            'reference string is re-encoded' => [
                'https://example.com/app/index.html?foo=bar',
                PathFormat::REFERENCE_PATH,
                'page=1&sort=name asc',
                [
                    'reference' => 'https://example.com/app/index.html?page=1&sort=name asc',
                    'access' => 'https://example.com/app/index.html?page=1&sort=name%20asc',
                ],
            ],
            'reference string with plus sign preserves literal plus' => [
                'https://example.com/app/index.html?foo=bar',
                PathFormat::REFERENCE_PATH,
                'foo=bar+baz',
                [
                    'reference' => 'https://example.com/app/index.html?foo=bar+baz',
                    'access' => 'https://example.com/app/index.html?foo=bar%2Bbaz',
                ],
            ],
            'array with null values keeps empty assignment' => [
                'https://example.com/app/index.html?foo=bar',
                PathFormat::REFERENCE_PATH,
                ['empty' => null, 'flag' => true],
                [
                    'reference' => 'https://example.com/app/index.html?empty=&flag=1',
                    'access' => 'https://example.com/app/index.html?empty=&flag=1',
                ],
            ],
            'null query clears existing parameters' => [
                'https://example.com/app/index.html?foo=bar',
                PathFormat::REFERENCE_PATH,
                null,
                [
                    'reference' => 'https://example.com/app/index.html',
                    'access' => 'https://example.com/app/index.html',
                ],
            ],
        ];
    }

    /**
     * @dataProvider withoutQueryProvider
     */
    public function testWithoutQueryRemovesKeys(string $input, PathFormat $format, string|array|null $keys, array $expected): void
    {
        $url = Path::url($input, $format);

        $updated = $url->withoutQuery($keys);

        $this->assertSame($expected['reference'], $updated->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expected['access'], $updated->toString(PathFormat::ACCESS_PATH));
    }

    public static function withoutQueryProvider(): iterable
    {
        return [
            'remove single key' => [
                'https://example.com/app/index.html?page=1&lang=en',
                PathFormat::REFERENCE_PATH,
                'page',
                [
                    'reference' => 'https://example.com/app/index.html?lang=en',
                    'access' => 'https://example.com/app/index.html?lang=en',
                ],
            ],
            'remove multiple keys' => [
                'https://example.com/app/index.html?lang=en&token=abc&foo=bar',
                PathFormat::REFERENCE_PATH,
                ['lang', 'token'],
                [
                    'reference' => 'https://example.com/app/index.html?foo=bar',
                    'access' => 'https://example.com/app/index.html?foo=bar',
                ],
            ],
            'remove grouped key clears nested values' => [
                'https://example.com/app/index.html?filter[active]=1&filter[name]=app&sort=asc',
                PathFormat::REFERENCE_PATH,
                ['filter'],
                [
                    'reference' => 'https://example.com/app/index.html?sort=asc',
                    'access' => 'https://example.com/app/index.html?sort=asc',
                ],
            ],
            'null clears entire query' => [
                'https://example.com/app/index.html?lang=en&token=abc',
                PathFormat::REFERENCE_PATH,
                null,
                [
                    'reference' => 'https://example.com/app/index.html',
                    'access' => 'https://example.com/app/index.html',
                ],
            ],
        ];
    }

    /**
     * @dataProvider cdHrefProvider
     */
    public function testUrlCdResolvesRelativeHrefs(string $input, PathFormat $format, string $href, array $expected): void
    {
        $url = Path::url($input, $format);

        $resolved = $url->cd($href);

        $this->assertSame($expected['reference'], $resolved->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expected['access'], $resolved->toString(PathFormat::ACCESS_PATH));
    }

    public static function cdHrefProvider(): iterable
    {
        return [
            'relative parent segment' => [
                'https://example.com/app/docs/index.html',
                PathFormat::REFERENCE_PATH,
                '../images/logo.png',
                [
                    'reference' => 'https://example.com/app/images/logo.png',
                    'access' => 'https://example.com/app/images/logo.png',
                ],
            ],
            'relative within same directory' => [
                'https://example.com/app/docs/index.html',
                PathFormat::REFERENCE_PATH,
                './scripts/app.js?v=1#top',
                [
                    'reference' => 'https://example.com/app/docs/scripts/app.js?v=1#top',
                    'access' => 'https://example.com/app/docs/scripts/app.js?v=1#top',
                ],
            ],
            'absolute url with same origin' => [
                'https://example.com/app/docs/index.html',
                PathFormat::REFERENCE_PATH,
                'https://example.com/app/docs/guide.html',
                [
                    'reference' => 'https://example.com/app/docs/guide.html',
                    'access' => 'https://example.com/app/docs/guide.html',
                ],
            ],
            'protocol relative keeps scheme' => [
                'https://example.com/app/docs/index.html',
                PathFormat::REFERENCE_PATH,
                '//example.com/assets/site.css',
                [
                    'reference' => 'https://example.com/assets/site.css',
                    'access' => 'https://example.com/assets/site.css',
                ],
            ],
            'multi level parent directories produce directory' => [
                'https://example.com/a/b/c/index.html',
                PathFormat::REFERENCE_PATH,
                '../../',
                [
                    'reference' => 'https://example.com/a/',
                    'access' => 'https://example.com/a/',
                ],
            ],
            'query only updates parameters' => [
                'https://example.com/app/index.html#intro',
                PathFormat::REFERENCE_PATH,
                '?page=2',
                [
                    'reference' => 'https://example.com/app/index.html?page=2',
                    'access' => 'https://example.com/app/index.html?page=2',
                ],
            ],
            'fragment only updates anchor' => [
                'https://example.com/app/index.html?page=1',
                PathFormat::REFERENCE_PATH,
                '#details',
                [
                    'reference' => 'https://example.com/app/index.html?page=1#details',
                    'access' => 'https://example.com/app/index.html?page=1#details',
                ],
            ],
        ];
    }

    /**
     * @dataProvider cdWithinBaseDirProvider
     */
    public function testUrlCdWithinBaseDir(string $base, string $working, string $href, string $expected): void
    {
        $baseDir = Path::url($base, PathFormat::ACCESS_URI);
        $url = Path::url($working, PathFormat::ACCESS_URI)->withBaseDir($baseDir);

        $resolved = $url->cd($href);

        $this->assertSame($expected, $resolved->toString(PathFormat::REFERENCE_PATH));
    }

    public static function cdWithinBaseDirProvider(): iterable
    {
        return [
            'navigate within assets tree' => [
                'https://example.com/app/assets/',
                'https://example.com/app/assets/images/logo.png',
                '../css/app.css',
                'https://example.com/app/assets/css/app.css',
            ],
            'navigate to sibling directory within base' => [
                'https://example.com/app/docs/',
                'https://example.com/app/docs/en/index.html',
                '../guide/',
                'https://example.com/app/docs/guide/',
            ],
            'protocol relative target within base' => [
                'https://example.com/app/',
                'https://example.com/app/docs/reference/',
                '//example.com/app/docs/api/',
                'https://example.com/app/docs/api/',
            ],
        ];
    }

    /**
     * @dataProvider cdOutsideBaseDirProvider
     */
    public function testUrlCdPreventsLeavingBase(string $base, string $working, string $href): void
    {
        $baseDir = Path::url($base, PathFormat::ACCESS_URI);
        $url = Path::url($working, PathFormat::ACCESS_URI)->withBaseDir($baseDir);

        $this->expectException(\OutOfBoundsException::class);
        $url->cd($href);
    }

    public static function cdOutsideBaseDirProvider(): iterable
    {
        return [
            'relative traversal beyond base' => [
                'https://example.com/app/assets/',
                'https://example.com/app/assets/images/logo.png',
                '../../secrets/config.php',
            ],
            'absolute different directory rejected' => [
                'https://example.com/app/assets/',
                'https://example.com/app/assets/images/logo.png',
                'https://example.com/admin/panel/',
            ],
            'protocol relative other host rejected' => [
                'https://example.com/app/assets/',
                'https://example.com/app/assets/images/logo.png',
                '//cdn.example.com/assets/app.css',
            ],
        ];
    }

    /**
     * @dataProvider withBaseDirValidationProvider
     */
    public function testWithBaseDirValidation(string $input, PathFormat $format, string $base, string $exceptionClass): void
    {
        $url = Path::url($input, $format);

        $this->expectException($exceptionClass);
        $url->withBaseDir($base);
    }

    public static function withBaseDirValidationProvider(): iterable
    {
        return [
            'base without trailing slash rejected' => [
                'https://example.com/app/index.html',
                PathFormat::REFERENCE_PATH,
                'https://example.com/app',
                \InvalidArgumentException::class,
            ],
            'different host rejected' => [
                'https://example.com/app/index.html',
                PathFormat::REFERENCE_PATH,
                'https://cdn.example.com/app/assets/',
                DifferentOriginException::class,
            ],
            'different scheme rejected' => [
                'https://example.com/app/index.html',
                PathFormat::REFERENCE_PATH,
                'http://example.com/app/',
                DifferentOriginException::class,
            ],
        ];
    }

    public function testWithBaseDirCanBeCleared(): void
    {
        $base = Path::url('https://example.com/app/', PathFormat::ACCESS_URI);
        $url = Path::url('https://example.com/app/docs/index.html', PathFormat::ACCESS_URI)->withBaseDir($base);

        $cleared = $url->withBaseDir(null);

        $this->assertSame(
            'https://example.com/app/docs/index.html',
            $cleared->toString(PathFormat::REFERENCE_PATH)
        );

        $result = $cleared->cd('../images/logo.png');

        $this->assertSame('https://example.com/app/images/logo.png', $result->toString(PathFormat::REFERENCE_PATH));
    }
}
