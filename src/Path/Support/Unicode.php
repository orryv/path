<?php

namespace Orryv\Path\Support;

final class Unicode
{
    private function __construct()
    {
    }

    public static function normalize(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($value, \Normalizer::FORM_C);
            if ($normalized !== false) {
                return $normalized;
            }
        }

        return $value;
    }

    /**
     * @return array{0:string,1:bool}
     */
    public static function normalizeSegment(string $value): array
    {
        $normalized = self::normalize($value);

        return [$normalized, $normalized !== $value];
    }

    public static function escapeSegment(string $value, bool $shouldEscape): string
    {
        if (!$shouldEscape) {
            return $value;
        }

        $result = '';
        foreach (preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) as $character) {
            $codePoint = mb_ord($character, 'UTF-8');
            if ($codePoint <= 0x7F) {
                $result .= $character;
                continue;
            }

            $result .= sprintf('\\u{%s}', strtoupper(dechex($codePoint)));
        }

        return $result;
    }
}
