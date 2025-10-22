<?php

declare(strict_types=1);

namespace Orryv;

final class XString
{
    /**
     * Windows reserved device names that require special handling.
     *
     * @var array<int, string>
     */
    private const WINDOWS_RESERVED_NAMES = [
        'CON',
        'PRN',
        'AUX',
        'NUL',
        'CLOCK$',
        'COM1',
        'COM2',
        'COM3',
        'COM4',
        'COM5',
        'COM6',
        'COM7',
        'COM8',
        'COM9',
        'LPT1',
        'LPT2',
        'LPT3',
        'LPT4',
        'LPT5',
        'LPT6',
        'LPT7',
        'LPT8',
        'LPT9',
    ];

    private function __construct()
    {
    }

    public static function toSafePath(string $value): string
    {
        $normalized = str_replace('\\', '/', $value);
        $isAbsolute = str_starts_with($normalized, '/');
        $hasTrailing = preg_match('~/(?:\s*)$~', rtrim($normalized)) === 1;

        $segments = preg_split('~\/+~', $normalized, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($segments)) {
            $segments = [];
        }

        $sanitized = [];
        foreach ($segments as $segment) {
            $sanitized[] = self::sanitizeGenericSegment($segment);
        }

        $result = $isAbsolute ? '/' : '';
        if ($sanitized !== []) {
            $result .= implode('/', $sanitized);
        }

        if ($result === '') {
            $result = '_';
        }

        if ($hasTrailing && !str_ends_with($result, '/')) {
            $result .= '/';
        }

        return $result;
    }

    public static function toSafeFileName(string $value): string
    {
        return self::sanitizeGenericSegment($value);
    }

    public static function toSafeFolderName(string $value): string
    {
        return self::sanitizeGenericSegment($value);
    }

    public static function encodeSafeFileName(string $value, bool $doubleEncode = false): string
    {
        return self::encodeGenericSegment($value, $doubleEncode);
    }

    public static function decodeSafeFileName(string $value): string
    {
        return self::decodeGenericSegment($value);
    }

    public static function encodeSafeFolderName(string $value, bool $doubleEncode = false): string
    {
        return self::encodeGenericSegment($value, $doubleEncode);
    }

    public static function decodeSafeFolderName(string $value): string
    {
        return self::decodeGenericSegment($value);
    }

    public static function encodeSafePath(string $value, bool $doubleEncode = false): string
    {
        $normalized = str_replace('\\', '/', $value);

        return self::encodeGenericPathString($normalized, $doubleEncode);
    }

    public static function decodeSafePath(string $value): string
    {
        $normalized = str_replace('\\', '/', $value);

        return self::decodeGenericPathString($normalized);
    }

    private static function encodeGenericPathString(string $path, bool $doubleEncode): string
    {
        if ($path === '') {
            return '';
        }

        $buffer = '';
        $result = '';
        $length = strlen($path);

        for ($index = 0; $index < $length; $index++) {
            $char = $path[$index];
            if ($char === '/') {
                if ($buffer !== '') {
                    $result .= self::encodeGenericSegment($buffer, $doubleEncode);
                    $buffer = '';
                }

                $result .= '/';

                continue;
            }

            $buffer .= $char;
        }

        if ($buffer !== '') {
            $result .= self::encodeGenericSegment($buffer, $doubleEncode);
        }

        return $result;
    }

    private static function decodeGenericPathString(string $path): string
    {
        if ($path === '') {
            return '';
        }

        $buffer = '';
        $result = '';
        $length = strlen($path);

        for ($index = 0; $index < $length; $index++) {
            $char = $path[$index];
            if ($char === '/') {
                if ($buffer !== '') {
                    $result .= self::decodeGenericSegment($buffer);
                    $buffer = '';
                }

                $result .= '/';

                continue;
            }

            $buffer .= $char;
        }

        if ($buffer !== '') {
            $result .= self::decodeGenericSegment($buffer);
        }

        return $result;
    }

    private static function encodeGenericSegment(string $segment, bool $doubleEncode): string
    {
        if ($segment === '') {
            return '';
        }

        return self::encodeWindowsSegment($segment, $doubleEncode);
    }

    private static function decodeGenericSegment(string $segment): string
    {
        if ($segment === '') {
            return '';
        }

        return self::decodePercentEncoded($segment);
    }

    private static function encodeWindowsSegment(string $segment, bool $doubleEncode): string
    {
        if ($segment === '') {
            return '';
        }

        $characters = self::splitCharacters($segment);
        if ($characters === []) {
            return '';
        }

        $count = count($characters);
        $trailing = 0;

        for ($index = $count - 1; $index >= 0; $index--) {
            $char = $characters[$index];
            if ($char === ' ' || $char === '.') {
                $trailing++;
                continue;
            }

            break;
        }

        $trimmed = rtrim($segment, " .");
        $isReserved = $trimmed !== '' && in_array(strtoupper($trimmed), self::WINDOWS_RESERVED_NAMES, true);

        $encoded = '';
        for ($index = 0; $index < $count; $index++) {
            $char = $characters[$index];

            if ($char === '%' && self::isPercentEncodedSequence($characters, $index)) {
                $first = strtoupper($characters[$index + 1]);
                $second = strtoupper($characters[$index + 2]);

                $encoded .= $doubleEncode
                    ? '%25' . $first . $second
                    : '%' . $first . $second;

                $index += 2;

                if ($doubleEncode
                    && isset($characters[$index + 1], $characters[$index + 2])
                    && strtoupper($characters[$index + 1]) === $first
                    && strtoupper($characters[$index + 2]) === $second
                ) {
                    $index += 2;
                }

                continue;
            }

            $shouldEncode = false;

            if (in_array($char, ['%', '<', '>', ':', '"', '/', '\\', '|', '?', '*'], true)) {
                $shouldEncode = true;
            } elseif (strlen($char) === 1) {
                $code = ord($char);
                if ($code < 0x20 || $code === 0x7F) {
                    $shouldEncode = true;
                }
            }

            if ($trailing > 0 && $index >= $count - $trailing && ($char === ' ' || $char === '.')) {
                $shouldEncode = true;
            }

            if ($isReserved && $index === 0) {
                $shouldEncode = true;
            }

            $encoded .= $shouldEncode
                ? self::percentEncodeBytes($char, $doubleEncode)
                : $char;
        }

        return $encoded;
    }

    private static function decodePercentEncoded(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $decoded = preg_replace_callback(
            '/%([0-9A-Fa-f]{2})/',
            static function (array $matches): string {
                return chr(hexdec($matches[1]));
            },
            $value
        );

        return is_string($decoded) ? $decoded : $value;
    }

    private static function percentEncodeBytes(string $char, bool $doubleEncode): string
    {
        if ($char === '') {
            return '';
        }

        $bytes = str_split($char);
        $encoded = '';

        foreach ($bytes as $byte) {
            $encoded .= sprintf('%%%02X', ord($byte));
        }

        if ($doubleEncode && $char !== '/') {
            $encoded = str_replace('%', '%25', $encoded);
        }

        return $encoded;
    }

    /**
     * @param array<int, string> $characters
     */
    private static function isPercentEncodedSequence(array $characters, int $index): bool
    {
        if (!isset($characters[$index + 1], $characters[$index + 2])) {
            return false;
        }

        $first = $characters[$index + 1];
        $second = $characters[$index + 2];

        if (strlen($first) !== 1 || strlen($second) !== 1) {
            return false;
        }

        return self::isUppercaseHexDigit($first) && self::isUppercaseHexDigit($second);
    }

    private static function isUppercaseHexDigit(string $char): bool
    {
        if ($char === '') {
            return false;
        }

        $code = ord($char);

        return ($code >= 0x30 && $code <= 0x39)
            || ($code >= 0x41 && $code <= 0x46);
    }

    private static function sanitizeGenericSegment(string $segment): string
    {
        $sanitized = self::sanitizeWindowsSegment($segment);
        $sanitized = str_replace([':', '/', '\\'], '_', $sanitized);

        if (trim($sanitized) === '') {
            $sanitized = '_';
        }

        if (strlen($sanitized) > 255) {
            $sanitized = substr($sanitized, 0, 255);
        }

        return $sanitized;
    }

    private static function sanitizeWindowsSegment(string $segment): string
    {
        $segment = str_replace("\0", '', $segment);

        $replaced = preg_replace('~[<>:"/\\\\|?*]~', '_', $segment);
        if (is_string($replaced)) {
            $segment = $replaced;
        }

        $removedControls = preg_replace('~[\x00-\x1F\x7F]~', '', $segment);
        if (is_string($removedControls)) {
            $segment = $removedControls;
        }

        $segment = trim($segment, " .");

        if ($segment === '' || $segment === '.' || $segment === '..') {
            $segment = '_';
        }

        $upper = strtoupper($segment);
        if (in_array($upper, self::WINDOWS_RESERVED_NAMES, true)) {
            $segment = '_' . $segment;
        }

        if (strlen($segment) > 255) {
            $segment = substr($segment, 0, 255);
        }

        return $segment;
    }

    /**
     * @return list<string>
     */
    private static function splitCharacters(string $characters): array
    {
        if ($characters === '') {
            return [];
        }

        if (preg_match('//u', $characters) === 1) {
            $parts = preg_split('//u', $characters, -1, PREG_SPLIT_NO_EMPTY);
            if ($parts !== false) {
                return $parts;
            }
        }

        return str_split($characters);
    }
}
