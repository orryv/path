<?php

namespace Tests\Unit;

use Orryv\Path;
use Orryv\Path\Enums\PathFormat;
use PHPUnit\Framework\TestCase;

class QueryEncodingTest extends TestCase
{
    public function testWithQueryEncodesUsingPhpQueryRfc3986(): void
    {
        $url = Path::url('https://example.com/search', PathFormat::ACCESS_URI);

        $updated = $url->withQuery([
            'q' => 'foo bar',
            'tags' => ['unicode ä', 'plus+symbol'],
            'page' => 2,
        ]);

        $expectedQuery = 'q=foo%20bar&tags%5B0%5D=unicode%20%C3%A4&tags%5B1%5D=plus%2Bsymbol&page=2';

        $this->assertSame("https://example.com/search?{$expectedQuery}", $updated->toString(PathFormat::ACCESS_URI));
        $this->assertSame('https://example.com/search?q=foo bar&tags[0]=unicode ä&tags[1]=plus+symbol&page=2', $updated->toString(PathFormat::REFERENCE_PATH));
    }

    public function testWithQueryAcceptsQueryStringButKeepsSpaceEncoding(): void
    {
        $url = Path::url('https://example.com/data', PathFormat::REFERENCE_PATH);

        $updated = $url->withQuery('a=1&b=two words');

        $this->assertSame('https://example.com/data?a=1&b=two%20words', $updated->toString(PathFormat::ACCESS_URI));
    }

    public function testWithoutQueryRemovesSpecificKeys(): void
    {
        $url = Path::url('https://example.com/app?foo=1&bar=two%20words&bar=again', PathFormat::ACCESS_URI);

        $withoutBar = $url->withoutQuery('bar');
        $this->assertSame('https://example.com/app?foo=1', $withoutBar->toString(PathFormat::ACCESS_URI));

        $withoutAll = $withoutBar->withoutQuery();
        $this->assertSame('https://example.com/app', $withoutAll->toString(PathFormat::ACCESS_URI));
    }

    public function testWithQuerySupportsNestedArraysAndNulls(): void
    {
        $url = Path::url('https://example.com/api', PathFormat::ACCESS_URI);

        $updated = $url->withQuery([
            'filter' => [
                'status' => ['new', 'archived'],
                'flags' => ['beta' => 'yes', 'deprecated' => 'no'],
            ],
            'empty' => null,
        ]);

        $expected = 'filter%5Bstatus%5D%5B0%5D=new&filter%5Bstatus%5D%5B1%5D=archived&filter%5Bflags%5D%5Bbeta%5D=yes&filter%5Bflags%5D%5Bdeprecated%5D=no&empty=';

        $this->assertSame("https://example.com/api?{$expected}", $updated->toString(PathFormat::ACCESS_URI));
        $this->assertSame('https://example.com/api?filter[status][0]=new&filter[status][1]=archived&filter[flags][beta]=yes&filter[flags][deprecated]=no&empty=', $updated->toString(PathFormat::REFERENCE_PATH));
    }

    public function testWithoutQueryRemovesMultipleKeys(): void
    {
        $url = Path::url('https://example.com/app?foo=1&foo=2&bar=three&baz=four', PathFormat::ACCESS_URI);

        $withoutFooBar = $url->withoutQuery(['foo', 'bar']);
        $this->assertSame('https://example.com/app?baz=four', $withoutFooBar->toString(PathFormat::ACCESS_URI));

        $withoutAll = $withoutFooBar->withoutQuery();
        $this->assertSame('https://example.com/app', $withoutAll->toString(PathFormat::ACCESS_URI));
    }
}
