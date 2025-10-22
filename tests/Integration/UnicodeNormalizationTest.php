<?php

namespace Tests\Integration;

use Orryv\Path;
use Orryv\Path\Enums\PathFormat;
use PHPUnit\Framework\TestCase;

class UnicodeNormalizationTest extends TestCase
{
    public function testEqualityTreatsNfcAndNfdAsSameReferencePath(): void
    {
        $nfcFolder = "r\u{E9}sum\u{E9}"; // résumé
        $nfdFolder = "re\u{0301}sume\u{0301}";

        $nfc = Path::dir("C:/{$nfcFolder}/", PathFormat::REFERENCE_PATH);
        $nfd = Path::dir("C:/{$nfdFolder}/", PathFormat::REFERENCE_PATH);

        $this->assertTrue($nfc->equals($nfd));
        $this->assertSame('C:/r\u{E9}sum\u{E9}/', $nfd->toString(PathFormat::REFERENCE_PATH));
    }

    public function testRelativizationNormalizesBeforeComputing(): void
    {
        $nfcFolder = "caf\u{E9}"; // café
        $nfdFolder = "cafe\u{0301}";
        $nfcFile = "men\u{FA}"; // menú
        $nfdFile = "menu\u{0301}";

        $base = Path::dir("/shared/{$nfdFolder}/", PathFormat::REFERENCE_PATH);
        $target = Path::file("/shared/{$nfcFolder}/{$nfdFile}.txt", PathFormat::REFERENCE_PATH);

        $this->assertSame('men\u{FA}.txt', $target->getRelativePathFrom($base, PathFormat::REFERENCE_PATH));
        $this->assertSame('men\u{FA}.txt', $target->getRelativePathFrom($base, PathFormat::ACCESS_PATH));
        $this->assertSame('men\u{FA}.txt', $target->getRelativePathFrom($base));
    }

    public function testDirectoryManipulationMaintainsCanonicalForm(): void
    {
        $nfc = "r\u{E9}sum\u{E9}";
        $nfd = "re\u{0301}sume\u{0301}";

        $original = Path::file("C:/{$nfc}/doc.txt", PathFormat::REFERENCE_PATH);
        $moved = $original->withDirectory("C:/{$nfd}/archives/");

        $this->assertSame("C:/{$nfc}/archives/doc.txt", $moved->toString(PathFormat::REFERENCE_PATH));
        $this->assertSame('C:\\r\u{E9}sum\u{E9}\\archives\\doc.txt', $moved->toString(PathFormat::ACCESS_PATH));

        $comparison = Path::file("C:/{$nfd}/archives/doc.txt", PathFormat::REFERENCE_PATH);
        $this->assertTrue($moved->equals($comparison));
    }
}
