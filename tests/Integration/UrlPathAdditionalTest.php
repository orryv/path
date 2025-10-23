<?php

namespace Tests\Integration;

use Orryv\Path;
use Orryv\Path\Enums\PathFormat;
use Orryv\Path\UrlPath;
use PHPUnit\Framework\TestCase;

class UrlPathAdditionalTest extends TestCase
{
    /**
     * @dataProvider urlEqualityProvider
     */
    public function testEqualsEvaluatesComponents(callable $firstFactory, callable $secondFactory, bool $expected): void
    {
        $first = $firstFactory();
        $second = $secondFactory();

        $this->assertInstanceOf(UrlPath::class, $first);
        $this->assertInstanceOf(UrlPath::class, $second);
        $this->assertSame($expected, $first->equals($second));
        $this->assertSame($expected, $second->equals($first));
    }

    public static function urlEqualityProvider(): iterable
    {
        yield 'reference and access formats are equal' => [
            static fn () => Path::url('https://example.com/app/index.html?foo=bar#frag', PathFormat::REFERENCE_PATH),
            static fn () => Path::url('https://example.com/app/index.html?foo=bar#frag', PathFormat::ACCESS_URI),
            true,
        ];

        yield 'different query ordering is not equal' => [
            static fn () => Path::url('https://example.com/app/index.html?foo=bar&baz=qux', PathFormat::REFERENCE_PATH),
            static fn () => Path::url('https://example.com/app/index.html?baz=qux&foo=bar', PathFormat::REFERENCE_PATH),
            false,
        ];

        yield 'different fragments are not equal' => [
            static fn () => Path::url('https://example.com/app/index.html?foo=bar#one', PathFormat::REFERENCE_PATH),
            static fn () => Path::url('https://example.com/app/index.html?foo=bar#two', PathFormat::REFERENCE_PATH),
            false,
        ];

        yield 'different trailing slash results differ' => [
            static fn () => Path::url('https://example.com/app/index.html', PathFormat::REFERENCE_PATH),
            static fn () => Path::url('https://example.com/app/index.html/', PathFormat::REFERENCE_PATH),
            false,
        ];

        yield 'user info matches between encoded forms' => [
            static fn () => Path::url('https://user:pass@example.com/app/', PathFormat::REFERENCE_PATH),
            static fn () => Path::url('https://user:pass@example.com/app/', PathFormat::ACCESS_URI),
            true,
        ];

        yield 'explicit port difference breaks equality' => [
            static fn () => Path::url('https://example.com/app/', PathFormat::REFERENCE_PATH),
            static fn () => Path::url('https://example.com:8443/app/', PathFormat::REFERENCE_PATH),
            false,
        ];

        yield 'base directory does not affect equality' => [
            static function () {
                $base = Path::url('https://example.com/app/', PathFormat::REFERENCE_PATH);

                return Path::url('https://example.com/app/docs/index.html', PathFormat::REFERENCE_PATH)->withBaseDir($base);
            },
            static fn () => Path::url('https://example.com/app/docs/index.html', PathFormat::REFERENCE_PATH),
            true,
        ];
    }

    /**
     * @dataProvider urlRelativeProvider
     */
    public function testGetRelativePathFromHandlesVariousBases(string $base, string $target, string $expectedReference, string $expectedAccess): void
    {
        $baseUrl = Path::url($base, PathFormat::REFERENCE_PATH);
        $targetUrl = Path::url($target, PathFormat::REFERENCE_PATH);

        $this->assertSame($expectedReference, $targetUrl->getRelativePathFrom($baseUrl, PathFormat::REFERENCE_PATH));
        $this->assertSame($expectedAccess, $targetUrl->getRelativePathFrom($baseUrl, PathFormat::ACCESS_URI));
    }

    public static function urlRelativeProvider(): iterable
    {
        yield 'file base to nested asset' => [
            'https://example.com/app/index.html',
            'https://example.com/app/assets/app.js',
            'assets/app.js',
            'assets/app.js',
        ];

        yield 'file base to sibling section' => [
            'https://example.com/app/index.html',
            'https://example.com/app/about/team.html',
            'about/team.html',
            'about/team.html',
        ];

        yield 'directory base without trailing slash' => [
            'https://example.com/app/docs',
            'https://example.com/app/docs/v1/index.html',
            'docs/v1/index.html',
            'docs/v1/index.html',
        ];

        yield 'identical directories return empty string' => [
            'https://example.com/app/docs/',
            'https://example.com/app/docs/',
            '',
            '',
        ];

        yield 'spaces are encoded for access format' => [
            'https://example.com/app/assets/',
            'https://example.com/app/assets/Open File.txt',
            'Open File.txt',
            'Open%20File.txt',
        ];
    }
}
