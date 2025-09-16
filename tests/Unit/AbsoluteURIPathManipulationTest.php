<?php

namespace Tests\Unit;

use Orryv\Path;
use Orryv\Path\Enums\Encoder;
use PHPUnit\Framework\TestCase;

class AbsoluteURIPathManipulationTest extends TestCase
{
    public function testQueryHelpersRoundTrip(): void
    {
        $path = Path::create('https://example.com/assets/image.png?foo=bar&flag')->asFile();

        $this->assertSame(['foo' => 'bar', 'flag' => null], $path->getQuery());
        $this->assertSame('foo=bar&flag', $path->getQueryString());

        $withoutQuery = $path->rmQuery();
        $this->assertNull($withoutQuery->getQuery());
        $this->assertSame('https://example.com/assets/image.png', (string) $withoutQuery->getAccessURI());

        $withQuery = $withoutQuery->withQuery(['page' => '1', 'filter' => null]);
        $this->assertSame('page=1&filter', $withQuery->getQueryString());
        $this->assertSame('https://example.com/assets/image.png?page=1&filter', (string) $withQuery->getAccessURI());
    }

    public function testWithQueryStringParsesValues(): void
    {
        $path = Path::create('https://example.com/search')->withQueryString('q=phpunit&debug');

        $this->assertSame(['q' => 'phpunit', 'debug' => null], $path->getQuery());
        $this->assertSame('https://example.com/search?q=phpunit&debug', (string) $path->getAccessURI());
    }

    public function testFragmentHelpers(): void
    {
        $path = Path::create('https://example.com/docs/index.html#top')->asFile();

        $this->assertSame('top', $path->getFragment());

        $withoutFragment = $path->rmFragment();
        $this->assertNull($withoutFragment->getFragment());
        $this->assertSame('https://example.com/docs/index.html', (string) $withoutFragment->getAccessURI());

        $withFragment = $withoutFragment->withFragment('section-2');
        $this->assertSame('section-2', $withFragment->getFragment());
        $this->assertSame('https://example.com/docs/index.html#section-2', (string) $withFragment->getAccessURI());
    }

    public function testUsernameAndPasswordUpdateRootFolder(): void
    {
        $path = Path::create('https://example.com/private/area')->asFolder();

        $withUsername = $path->withUsername('alice');
        $withPassword = $withUsername->withPassword('secret');

        $this->assertSame('alice', $withPassword->getUsername());
        $this->assertSame('secret', $withPassword->getPassword());
        $this->assertSame('https://alice:secret@example.com/private/area', (string) $withPassword->getAccessURI());
    }

    public function testWithPortProducesNewInstance(): void
    {
        $path = Path::create('https://example.com/service');

        $withPort = $path->withPort(8443);

        $this->assertNotSame($path, $withPort);
        $this->assertNull($path->getPort());
        $this->assertSame(8443, $withPort->getPort());
    }

    public function testWithEncodingDecodesReferencePath(): void
    {
        $path = Path::create('https://example.com/path%20with%20spaces/encoded%2Fsegment')->withEncoding(Encoder::URLENCODE);

        $this->assertSame('https://example.com/path with spaces/encoded/segment', (string) $path->getReferencePath());
    }
}

