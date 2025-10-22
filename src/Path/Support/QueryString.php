<?php

namespace Orryv\Path\Support;

/**
 * Lightweight query string representation that keeps insertion order and
 * duplicate keys intact. It stores values in their encoded form and can render
 * both encoded (ACCESS_URI) and decoded (REFERENCE_PATH) representations.
 */
final class QueryString
{
    /**
     * @var list<array{rawKey:string, decodedKey:string, rawValue:string|null, hasEquals:bool}>
     */
    private array $entries;

    private function __construct(array $entries)
    {
        $this->entries = $entries;
    }

    public static function fromEncoded(?string $query): self
    {
        if ($query === null || $query === '') {
            return new self([]);
        }

        $parts = explode('&', $query);
        $entries = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            [$rawKey, $rawValue, $hasEquals] = self::splitPair($part);

            $entries[] = [
                'rawKey' => $rawKey,
                'decodedKey' => rawurldecode($rawKey),
                'rawValue' => $rawValue,
                'hasEquals' => $hasEquals,
            ];
        }

        return new self($entries);
    }

    public static function fromArray(array $query): self
    {
        $normalized = self::normalizeArrayValues($query);
        $encoded = http_build_query($normalized, '', '&', PHP_QUERY_RFC3986);

        return self::fromEncoded($encoded === '' ? null : $encoded);
    }

    public static function fromReferenceString(string $query): self
    {
        if ($query === '') {
            return new self([]);
        }

        $parts = explode('&', $query);
        $encodedParts = [];

        foreach ($parts as $part) {
            [$key, $value, $hasEquals] = self::splitPair($part);

            $encodedKey = rawurlencode(rawurldecode($key));
            if ($hasEquals) {
                $encodedValue = rawurlencode(rawurldecode($value ?? ''));
                $encodedParts[] = $encodedKey . '=' . $encodedValue;
            } else {
                $encodedParts[] = $encodedKey;
            }
        }

        return self::fromEncoded(implode('&', $encodedParts));
    }

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    public function encoded(): ?string
    {
        if ($this->entries === []) {
            return null;
        }

        $parts = [];
        foreach ($this->entries as $entry) {
            if ($entry['hasEquals']) {
                $parts[] = $entry['rawKey'] . '=' . ($entry['rawValue'] ?? '');
            } else {
                $parts[] = $entry['rawKey'];
            }
        }

        return implode('&', $parts);
    }

    public function decoded(): ?string
    {
        $encoded = $this->encoded();
        if ($encoded === null) {
            return null;
        }

        return rawurldecode($encoded);
    }

    public function withArray(array $query): self
    {
        return self::fromArray($query);
    }

    public function withReferenceString(string $query): self
    {
        return self::fromReferenceString($query);
    }

    /**
     * @param list<string>|null $keys
     */
    public function withoutKeys(?array $keys): self
    {
        if ($keys === null) {
            return new self([]);
        }

        if ($keys === []) {
            return new self($this->entries);
        }

        $normalized = array_map(static fn (string $key): string => mb_strtolower($key), $keys);

        $entries = array_filter(
            $this->entries,
            static function (array $entry) use ($normalized): bool {
                $base = self::baseKey($entry['decodedKey']);

                return !in_array(mb_strtolower($base), $normalized, true);
            }
        );

        return new self(array_values($entries));
    }

    private static function normalizeArrayValues(array $query): array
    {
        $normalized = [];
        foreach ($query as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = self::normalizeArrayValues($value);
                continue;
            }

            $normalized[$key] = $value === null ? '' : $value;
        }

        return $normalized;
    }

    private static function baseKey(string $decodedKey): string
    {
        $pos = strpos($decodedKey, '[');

        return $pos === false ? $decodedKey : substr($decodedKey, 0, $pos);
    }

    /**
     * @return array{0:string,1:string|null,2:bool}
     */
    private static function splitPair(string $part): array
    {
        $pieces = explode('=', $part, 2);

        $rawKey = $pieces[0];
        $hasEquals = array_key_exists(1, $pieces);
        $rawValue = $hasEquals ? ($pieces[1] ?? '') : null;

        return [$rawKey, $rawValue, $hasEquals];
    }
}
