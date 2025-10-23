<?php

namespace Tests\Integration;

use Orryv\Path;
use Orryv\Path\Enums\PathFormat;
use PHPUnit\Framework\TestCase;

class UrlPathQueryIntegrationTest extends TestCase
{
    /**
     * @dataProvider withQueryProvider
     */
    public function testWithQueryAcceptsVariousInputs(callable $factory, array|string|null $query, string $expectedReference, string $expectedAccess): void
    {
        $url = $factory();

        $updated = $url->withQuery($query);

        $this->assertSame($expectedReference, $updated->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expectedAccess, $updated->toString(PathFormat::ACCESS_URI));
    }

    public static function withQueryProvider(): iterable
    {
        yield 'array input with nested parameters' => [
            static fn () => Path::url('https://example.com/app/index.php', PathFormat::REFERENCE_PATH),
            ['foo' => 'bar', 'filter' => ['status' => 'active']],
            'https://example.com/app/index.php?foo=bar&filter[status]=active',
            'https://example.com/app/index.php?foo=bar&filter%5Bstatus%5D=active',
        ];

        yield 'string input preserves ordering' => [
            static fn () => Path::url('https://example.com/app/index.php', PathFormat::REFERENCE_PATH),
            'b=2&a=1',
            'https://example.com/app/index.php?b=2&a=1',
            'https://example.com/app/index.php?b=2&a=1',
        ];

        yield 'reference string with spaces and unicode is encoded' => [
            static fn () => Path::url('https://example.com/app/index.php', PathFormat::REFERENCE_PATH),
            'status=needs review&token=α',
            'https://example.com/app/index.php?status=needs review&token=α',
            'https://example.com/app/index.php?status=needs%20review&token=%CE%B1',
        ];

        yield 'null clears the existing query' => [
            static fn () => Path::url('https://example.com/app/index.php?foo=bar', PathFormat::REFERENCE_PATH),
            null,
            'https://example.com/app/index.php',
            'https://example.com/app/index.php',
        ];

        yield 'empty array removes query parameters' => [
            static fn () => Path::url('https://example.com/app/index.php?foo=bar', PathFormat::REFERENCE_PATH),
            [],
            'https://example.com/app/index.php',
            'https://example.com/app/index.php',
        ];
    }

    /**
     * @dataProvider withoutQueryProvider
     */
    public function testWithoutQueryRemovesKeys(callable $factory, array|string|null $keys, string $expectedReference, string $expectedAccess): void
    {
        $url = $factory();

        $updated = $url->withoutQuery($keys);

        $this->assertSame($expectedReference, $updated->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame($expectedAccess, $updated->toString(PathFormat::ACCESS_URI));
    }

    public static function withoutQueryProvider(): iterable
    {
        yield 'remove single key' => [
            static fn () => Path::url('https://example.com/app/index.php?token=1&lang=en', PathFormat::REFERENCE_PATH),
            'token',
            'https://example.com/app/index.php?lang=en',
            'https://example.com/app/index.php?lang=en',
        ];

        yield 'remove multiple keys with base key matching' => [
            static fn () => Path::url('https://example.com/app/index.php?filter[name]=x&filter[age]=y&page=2', PathFormat::REFERENCE_PATH),
            ['filter', 'page'],
            'https://example.com/app/index.php',
            'https://example.com/app/index.php',
        ];

        yield 'null clears entire query string' => [
            static fn () => Path::url('https://example.com/app/index.php?foo=bar', PathFormat::REFERENCE_PATH),
            null,
            'https://example.com/app/index.php',
            'https://example.com/app/index.php',
        ];

        yield 'empty array keeps existing query' => [
            static fn () => Path::url('https://example.com/app/index.php?foo=bar&lang=en', PathFormat::REFERENCE_PATH),
            [],
            'https://example.com/app/index.php?foo=bar&lang=en',
            'https://example.com/app/index.php?foo=bar&lang=en',
        ];
    }

    public function testWithBaseDirNullAllowsUnrestrictedNavigation(): void
    {
        $base = Path::url('https://example.com/app/', PathFormat::REFERENCE_PATH);
        $asset = Path::url('https://example.com/app/assets/css/app.css', PathFormat::REFERENCE_PATH)->withBaseDir($base);

        $unrestricted = $asset->withBaseDir(null);
        $result = $unrestricted->cd('../../../index.html');

        $this->assertSame('https://example.com/index.html', $result->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('https://example.com/index.html', $result->toString(PathFormat::ACCESS_URI));
    }
}
