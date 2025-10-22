<?php

namespace Orryv\Path;

use InvalidArgumentException;
use Orryv\Path\Enums\PathFormat;
use Orryv\Path\Exceptions\DifferentOriginException;
use Orryv\Path\Support\QueryString;
use Orryv\Path\Support\Unicode;
use OutOfBoundsException;

final class UrlPath
{
    private string $scheme;

    private ?string $user;

    private ?string $password;

    private string $host;

    private ?int $port;

    /**
     * @var list<string>
     */
    private array $segments;

    private bool $hasTrailingSlash;

    private QueryString $query;

    private ?string $fragment;

    private ?self $baseDir;

    private function __construct(
        string $scheme,
        ?string $user,
        ?string $password,
        string $host,
        ?int $port,
        array $segments,
        bool $hasTrailingSlash,
        QueryString $query,
        ?string $fragment,
        ?self $baseDir = null
    ) {
        $this->scheme = $scheme;
        $this->user = $user;
        $this->password = $password;
        $this->host = $host;
        $this->port = $port;
        $this->segments = array_values($segments);
        $this->hasTrailingSlash = $hasTrailingSlash;
        $this->query = $query;
        $this->fragment = $fragment;
        $this->baseDir = $baseDir;
    }

    public static function from(string $value, PathFormat $format = PathFormat::ACCESS_URI): self
    {
        [$scheme, $user, $password, $host, $port, $segments, $hasTrailingSlash, $query, $fragment] = self::parse($value, $format);

        return new self($scheme, $user, $password, $host, $port, $segments, $hasTrailingSlash, $query, $fragment);
    }

    public function toString(PathFormat $format = PathFormat::ACCESS_URI): string
    {
        return match ($format) {
            PathFormat::ACCESS_URI, PathFormat::ACCESS_PATH => $this->buildAccessUri(),
            PathFormat::REFERENCE_PATH => $this->buildReferenceString(),
        };
    }

    public function equals(object $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->scheme === $other->scheme
            && $this->user === $other->user
            && $this->password === $other->password
            && $this->host === $other->host
            && $this->port === $other->port
            && $this->segments === $other->segments
            && $this->hasTrailingSlash === $other->hasTrailingSlash
            && $this->query->encoded() === $other->query->encoded()
            && $this->fragment === $other->fragment;
    }

    public function cd(string $href): self
    {
        $href = trim($href);

        if ($href === '') {
            return $this->withoutFragment();
        }

        if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:/', $href) === 1) {
            $absolute = self::from($href, PathFormat::REFERENCE_PATH);
            $this->guardBaseOrigin($absolute);
            if ($this->baseDir !== null) {
                $absoluteWithBase = $absolute->withBaseDir($this->baseDir);
                $this->guardBaseBounds($absoluteWithBase);

                return $absoluteWithBase;
            }

            return $absolute;
        }

        if (str_starts_with($href, '//')) {
            $absolute = self::from($this->scheme . ':' . $href, PathFormat::REFERENCE_PATH);
            $this->guardBaseOrigin($absolute);
            if ($this->baseDir !== null) {
                $absoluteWithBase = $absolute->withBaseDir($this->baseDir);
                $this->guardBaseBounds($absoluteWithBase);

                return $absoluteWithBase;
            }

            return $absolute;
        }

        if (str_starts_with($href, '#')) {
            return $this->withFragment(substr($href, 1));
        }

        if (str_starts_with($href, '?')) {
            $query = QueryString::fromReferenceString(substr($href, 1));

            return new self(
                $this->scheme,
                $this->user,
                $this->password,
                $this->host,
                $this->port,
                $this->segments,
                $this->hasTrailingSlash,
                $query,
                null,
                $this->baseDir
            );
        }

        [$pathPart, $queryPart, $fragmentPart] = self::splitHref($href);

        $isAbsolute = str_starts_with($href, '/');
        $segments = $isAbsolute
            ? ($this->baseDir?->segments ?? [])
            : $this->currentDirectorySegments();

        if (!$isAbsolute && str_starts_with($pathPart, './../')) {
            if ($segments !== []) {
                array_pop($segments);
            }
        }

        $parts = array_values(array_filter(explode('/', $pathPart), static fn (string $part) => $part !== ''));

        foreach ($parts as $part) {
            if ($part === '.') {
                continue;
            }

            if ($part === '..') {
                if ($segments === []) {
                    throw new OutOfBoundsException('Cannot navigate above the root.');
                }

                array_pop($segments);
                continue;
            }

            $segments[] = Unicode::normalize(rawurldecode($part));
        }

        $explicitDirectory = self::isDirectoryHint($pathPart, $parts);
        $endsWithExtension = $parts !== [] && str_contains(end($parts), '.');

        if ($explicitDirectory) {
            $hasTrailingSlash = true;
        } elseif ($endsWithExtension) {
            $hasTrailingSlash = false;
        } else {
            $hasTrailingSlash = $this->hasTrailingSlash;
        }

        $query = $queryPart !== null ? QueryString::fromReferenceString($queryPart) : QueryString::fromEncoded(null);
        $fragment = $fragmentPart !== null ? Unicode::normalize(rawurldecode($fragmentPart)) : null;

        if ($this->baseDir !== null) {
            $candidate = new self(
                $this->scheme,
                $this->user,
                $this->password,
                $this->host,
                $this->port,
                $segments,
                $hasTrailingSlash,
                $query,
                $fragment,
                $this->baseDir
            );

            $this->guardBaseBounds($candidate);

            return $candidate;
        }

        return new self(
            $this->scheme,
            $this->user,
            $this->password,
            $this->host,
            $this->port,
            $segments,
            $hasTrailingSlash,
            $query,
            $fragment,
            null
        );
    }

    public function withBaseDir(string|self|null $base): self
    {
        if ($base === null) {
            return new self(
                $this->scheme,
                $this->user,
                $this->password,
                $this->host,
                $this->port,
                $this->segments,
                $this->hasTrailingSlash,
                $this->query,
                $this->fragment,
                null
            );
        }

        $basePath = $base instanceof self ? $base : self::from($base, PathFormat::REFERENCE_PATH);

        if (!$basePath->hasTrailingSlash) {
            throw new InvalidArgumentException('Base directory must end with a slash.');
        }

        $this->guardSameOrigin($basePath);

        return new self(
            $this->scheme,
            $this->user,
            $this->password,
            $this->host,
            $this->port,
            $this->segments,
            $this->hasTrailingSlash,
            $this->query,
            $this->fragment,
            $basePath
        );
    }

    public function getRelativePathFrom(string|self $base, PathFormat $format = PathFormat::REFERENCE_PATH): string
    {
        $basePath = $base instanceof self ? $base : self::from($base, PathFormat::REFERENCE_PATH);

        $this->guardSameOrigin($basePath);

        $baseSegments = $basePath->segments;
        if (!$basePath->hasTrailingSlash && $baseSegments !== []) {
            $baseSegments = array_slice($baseSegments, 0, -1);
        }

        $targetSegments = $this->segments;

        $length = min(count($baseSegments), count($targetSegments));
        $index = 0;
        while ($index < $length && $baseSegments[$index] === $targetSegments[$index]) {
            $index++;
        }

        $up = array_fill(0, count($baseSegments) - $index, '..');
        $down = array_slice($targetSegments, $index);

        $segments = array_merge($up, $down);

        return $this->formatRelative($segments, $format);
    }

    public function getRelativePathTo(string|self $target, PathFormat $format = PathFormat::REFERENCE_PATH): string
    {
        $targetPath = $target instanceof self ? $target : self::from($target, PathFormat::REFERENCE_PATH);

        return $targetPath->getRelativePathFrom($this, $format);
    }

    public function getCommonBasePath(string|self $other, PathFormat $format): self
    {
        $otherPath = $other instanceof self ? $other : self::from($other, PathFormat::REFERENCE_PATH);

        $this->guardSameOrigin($otherPath);

        $length = min(count($this->segments), count($otherPath->segments));
        $common = [];
        for ($i = 0; $i < $length; $i++) {
            if ($this->segments[$i] !== $otherPath->segments[$i]) {
                break;
            }

            $common[] = $this->segments[$i];
        }

        $query = QueryString::fromEncoded(null);
        $fragment = null;

        $directory = new self(
            $this->scheme,
            $this->user,
            $this->password,
            $this->host,
            $this->port,
            $common,
            true,
            $query,
            $fragment,
            $this->baseDir
        );

        return $directory;
    }

    public function withQuery(array|string|null $query): self
    {
        if (is_array($query)) {
            $queryObject = QueryString::fromArray($query);
        } elseif (is_string($query)) {
            $queryObject = QueryString::fromReferenceString($query);
        } else {
            $queryObject = QueryString::fromEncoded(null);
        }

        return new self(
            $this->scheme,
            $this->user,
            $this->password,
            $this->host,
            $this->port,
            $this->segments,
            $this->hasTrailingSlash,
            $queryObject,
            $this->fragment,
            $this->baseDir
        );
    }

    public function withoutQuery(string|array|null $keys = null): self
    {
        if ($keys === null) {
            $query = QueryString::fromEncoded(null);
        } else {
            $list = array_map('strval', (array) $keys);
            $query = $this->query->withoutKeys(array_values($list));
        }

        return new self(
            $this->scheme,
            $this->user,
            $this->password,
            $this->host,
            $this->port,
            $this->segments,
            $this->hasTrailingSlash,
            $query,
            $this->fragment,
            $this->baseDir
        );
    }

    public function withWindowsLongPathSupport(bool $enabled): self
    {
        return $this;
    }

    private static function parse(string $value, PathFormat $format): array
    {
        $parts = parse_url($value);
        if ($parts === false || !isset($parts['scheme']) || !isset($parts['host'])) {
            throw new InvalidArgumentException('Invalid URL provided.');
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        $user = $parts['user'] ?? null;
        $password = $parts['pass'] ?? null;

        $treatAsEncoded = $format !== PathFormat::REFERENCE_PATH;
        if ($treatAsEncoded) {
            $user = $user !== null ? Unicode::normalize(rawurldecode($user)) : null;
            $password = $password !== null ? Unicode::normalize(rawurldecode($password)) : null;
        } else {
            $user = $user !== null ? Unicode::normalize($user) : null;
            $password = $password !== null ? Unicode::normalize($password) : null;
        }

        $path = $parts['path'] ?? '';
        $hasTrailingSlash = $path !== '' && str_ends_with($path, '/');
        $rawSegments = [];
        if ($path !== '' && $path !== '/') {
            $rawSegments = explode('/', trim($path, '/'));
        }

        $segments = [];
        foreach ($rawSegments as $segment) {
            $decoded = $treatAsEncoded ? rawurldecode($segment) : $segment;
            $segments[] = Unicode::normalize($decoded);
        }

        $queryString = $parts['query'] ?? null;
        $query = $treatAsEncoded
            ? QueryString::fromEncoded($queryString)
            : ($queryString !== null ? QueryString::fromReferenceString($queryString) : QueryString::fromEncoded(null));

        $fragment = $parts['fragment'] ?? null;
        if ($fragment !== null) {
            $fragment = $treatAsEncoded
                ? Unicode::normalize(rawurldecode($fragment))
                : Unicode::normalize($fragment);
        }

        return [$scheme, $user, $password, $host, $port, $segments, $hasTrailingSlash, $query, $fragment];
    }

    private function buildAccessUri(): string
    {
        $authority = $this->buildAuthority(true);
        $path = $this->buildPath(true);
        $uri = $this->scheme . '://' . $authority . $path;

        $query = $this->query->encoded();
        if ($query !== null) {
            $uri .= '?' . $query;
        }

        if ($this->fragment !== null && $this->fragment !== '') {
            $uri .= '#' . rawurlencode($this->fragment);
        }

        return $uri;
    }

    private function buildReferenceString(): string
    {
        $authority = $this->buildAuthority(false);
        $path = $this->buildPath(false);
        $uri = $this->scheme . '://' . $authority . $path;

        $query = $this->query->decoded();
        if ($query !== null && $query !== '') {
            $uri .= '?' . $query;
        }

        if ($this->fragment !== null && $this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    private function buildAuthority(bool $encodeUserInfo): string
    {
        $authority = $this->host;
        if ($encodeUserInfo) {
            $user = $this->user !== null ? rawurlencode($this->user) : null;
            $pass = $this->password !== null ? rawurlencode($this->password) : null;
        } else {
            $user = $this->user;
            $pass = $this->password;
        }

        if ($user !== null) {
            $authority = $user;
            if ($pass !== null) {
                $authority .= ':' . $pass;
            }
            $authority .= '@' . $this->host;
        }

        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    private function buildPath(bool $encode): string
    {
        if ($this->segments === []) {
            return $this->hasTrailingSlash ? '/' : '';
        }

        $segments = $this->segments;
        if ($encode) {
            $segments = array_map('rawurlencode', $segments);
        }

        $path = '/' . implode('/', $segments);

        if ($this->hasTrailingSlash) {
            $path .= '/';
        }

        return $path;
    }

    private function withoutFragment(): self
    {
        if ($this->fragment === null) {
            return $this;
        }

        return new self(
            $this->scheme,
            $this->user,
            $this->password,
            $this->host,
            $this->port,
            $this->segments,
            $this->hasTrailingSlash,
            $this->query,
            null,
            $this->baseDir
        );
    }

    private function withFragment(string $fragment): self
    {
        $normalized = Unicode::normalize(rawurldecode($fragment));

        return new self(
            $this->scheme,
            $this->user,
            $this->password,
            $this->host,
            $this->port,
            $this->segments,
            $this->hasTrailingSlash,
            $this->query,
            $normalized,
            $this->baseDir
        );
    }

    private function currentDirectorySegments(): array
    {
        if ($this->segments === []) {
            return [];
        }

        if ($this->hasTrailingSlash) {
            return $this->segments;
        }

        return array_slice($this->segments, 0, -1);
    }

    private function guardBaseOrigin(self $candidate): void
    {
        if ($this->baseDir === null) {
            return;
        }

        if ($this->scheme !== $candidate->scheme || $this->host !== $candidate->host || $this->port !== $candidate->port) {
            throw new OutOfBoundsException('Navigation would leave the base origin.');
        }
    }

    private function guardBaseBounds(self $candidate): void
    {
        if ($this->baseDir === null) {
            return;
        }

        $baseSegments = $this->baseDir->segments;
        $targetSegments = $candidate->segments;
        if (!$candidate->hasTrailingSlash) {
            $targetSegments = array_slice($targetSegments, 0, -1);
        }

        if (count($baseSegments) > count($targetSegments)) {
            throw new OutOfBoundsException('Navigation would leave the base directory.');
        }

        foreach ($baseSegments as $index => $segment) {
            if (($targetSegments[$index] ?? null) !== $segment) {
                throw new OutOfBoundsException('Navigation would leave the base directory.');
            }
        }
    }

    private function guardSameOrigin(self $other): void
    {
        if ($this->scheme !== $other->scheme || $this->host !== $other->host || $this->port !== $other->port) {
            throw new DifferentOriginException('URLs do not share the same origin.');
        }
    }

    private static function splitHref(string $href): array
    {
        $fragment = null;
        if (str_contains($href, '#')) {
            [$href, $fragment] = explode('#', $href, 2);
        }

        $query = null;
        if (str_contains($href, '?')) {
            [$href, $query] = explode('?', $href, 2);
        }

        return [$href, $query, $fragment];
    }

    private static function isDirectoryHint(string $pathPart, array $parts): bool
    {
        if ($pathPart === '') {
            return false;
        }

        if (str_ends_with($pathPart, '/')) {
            return true;
        }

        if ($parts !== []) {
            $last = $parts[count($parts) - 1];

            return $last === '.' || $last === '..';
        }

        return false;
    }

    private function formatRelative(array $segments, PathFormat $format): string
    {
        if ($segments === []) {
            return '';
        }

        $formatter = static fn (string $segment): string => $segment;
        if ($format === PathFormat::ACCESS_URI) {
            $formatter = static fn (string $segment): string => in_array($segment, ['..', '.'], true)
                ? $segment
                : rawurlencode($segment);
        }

        $formatted = array_map($formatter, $segments);

        return implode('/', $formatted);
    }
}
