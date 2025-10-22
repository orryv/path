<?php

namespace Tests\Unit;

use Orryv\XString;
use PHPUnit\Framework\TestCase;

class XStringSafePathTest extends TestCase
{
    public function testToSafePathSanitizesMixedPlatformValue(): void
    {
        $this->assertSame('C_/Temp/_AUX/report_.txt', XString::toSafePath('C:\\Temp\\AUX\\report?.txt'));
    }

    public function testToSafePathPreservesTrailingSlash(): void
    {
        $this->assertSame('workspace/', XString::toSafePath('workspace\\'));
    }

    public function testToSafePathFallsBackToUnderscoreForEmptyValues(): void
    {
        $this->assertSame('_', XString::toSafePath('   '));
    }

    public function testEncodeSafePathEncodesForbiddenCharacters(): void
    {
        $this->assertSame('C%3A/logs/error%3F.txt', XString::encodeSafePath('C:\\logs\\error?.txt'));
    }

    public function testEncodeSafePathHandlesUncReservedNames(): void
    {
        $this->assertSame('//server/share/%41UX', XString::encodeSafePath('\\\\server\\share\\AUX'));
    }

    public function testEncodeSafePathSupportsDoubleEncodingToggle(): void
    {
        $this->assertSame('Archive%202024/Logs%3F.txt', XString::encodeSafePath('Archive%202024/Logs?.txt'));
        $this->assertSame('Archive%252024/Logs%253F.txt', XString::encodeSafePath('Archive%202024/Logs?.txt', true));
    }

    public function testDecodeSafePathRestoresEscapedSegments(): void
    {
        $this->assertSame('reports/100% ready', XString::decodeSafePath('reports/100%25 ready'));
    }

    public function testEncodeSafeFileNameAppliesPercentEncoding(): void
    {
        $this->assertSame('report%3F.txt', XString::encodeSafeFileName('report?.txt'));
    }

    public function testDecodeSafeFileNameReversesEncoding(): void
    {
        $this->assertSame('report?.txt', XString::decodeSafeFileName('report%3F.txt'));
    }

    public function testToSafeFileNameNeutralisesReservedNames(): void
    {
        $this->assertSame('_CON', XString::toSafeFileName('CON'));
    }

    public function testToSafeFolderNameSanitizesSeparators(): void
    {
        $this->assertSame('data_logs', XString::toSafeFolderName('data/logs'));
    }
}
