<?php
declare(strict_types=1);

namespace FacturaScripts\Plugins\WidgetNif\Test;

use FacturaScripts\Plugins\WidgetNif\Lib\NifValidator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for NifValidator.
 *
 * Runs standalone — no FacturaScripts installation required.
 * Covers NIF (mod-23), NIE (X/Y/Z prefix), CIF (all entity types), normalization, edge cases.
 */
class NifValidatorTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Normalization
    // -------------------------------------------------------------------------

    public function testNormalizeUppercase(): void
    {
        $this->assertSame('12345678Z', NifValidator::normalize('12345678z'));
    }

    public function testNormalizeSpaces(): void
    {
        $this->assertSame('12345678Z', NifValidator::normalize('12 345 678 Z'));
    }

    public function testNormalizeDashes(): void
    {
        $this->assertSame('12345678Z', NifValidator::normalize('12-345-678-Z'));
    }

    public function testNormalizeDots(): void
    {
        $this->assertSame('12345678Z', NifValidator::normalize('12.345.678Z'));
    }

    public function testNormalizeTrim(): void
    {
        $this->assertSame('12345678Z', NifValidator::normalize('  12345678Z  '));
    }

    // -------------------------------------------------------------------------
    // Type detection
    // -------------------------------------------------------------------------

    public function testDetectTypeNif(): void
    {
        $this->assertSame('nif', NifValidator::detectType('12345678Z'));
    }

    public function testDetectTypeNieX(): void
    {
        $this->assertSame('nie', NifValidator::detectType('X1234567L'));
    }

    public function testDetectTypeNieY(): void
    {
        $this->assertSame('nie', NifValidator::detectType('Y1234567M'));
    }

    public function testDetectTypeNieZ(): void
    {
        $this->assertSame('nie', NifValidator::detectType('Z1234567X'));
    }

    public function testDetectTypeCif(): void
    {
        $this->assertSame('cif', NifValidator::detectType('B12345674'));
    }

    public function testDetectTypePassport(): void
    {
        $this->assertSame('passport', NifValidator::detectType('ABC123456'));
    }

    public function testDetectTypeUnknown(): void
    {
        $this->assertSame('unknown', NifValidator::detectType('INVALID!!'));
    }

    // -------------------------------------------------------------------------
    // NIF validation
    // -------------------------------------------------------------------------

    public function testValidNifKnownValues(): void
    {
        // 12345678 % 23 = 14 → letter index 14 = 'Z'
        $this->assertTrue(NifValidator::validateNif('12345678Z'));
    }

    public function testValidNifZeroPrefix(): void
    {
        // 00000000 % 23 = 0 → letter index 0 = 'T'
        $this->assertTrue(NifValidator::validateNif('00000000T'));
    }

    public function testInvalidNifWrongLetter(): void
    {
        $this->assertFalse(NifValidator::validateNif('12345678A'));
    }

    public function testInvalidNifTooShort(): void
    {
        $this->assertFalse(NifValidator::validateNif('1234567Z'));
    }

    public function testInvalidNifTooLong(): void
    {
        $this->assertFalse(NifValidator::validateNif('123456789Z'));
    }

    public function testInvalidNifNoLetter(): void
    {
        $this->assertFalse(NifValidator::validateNif('12345678'));
    }

    public function testNifCaseInsensitive(): void
    {
        // normalize is called inside validateNif
        $this->assertTrue(NifValidator::validateNif('12345678z'));
    }

    public function testNifWithSpaces(): void
    {
        $this->assertTrue(NifValidator::validateNif('1234 5678 Z'));
    }

    // -------------------------------------------------------------------------
    // NIE validation
    // -------------------------------------------------------------------------

    public function testValidNieX(): void
    {
        // X1234567: prefix X→0, number 01234567 = 1234567, 1234567 % 23 = 19 → 'L'
        $this->assertTrue(NifValidator::validateNie('X1234567L'));
    }

    public function testValidNieY(): void
    {
        // Y0000000: prefix Y→1, number 10000000, 10000000 % 23 = ?
        // Precomputed: 10000000 % 23 = 10000000 - 23*434782 = 10000000 - 9999886 = 114... let me just verify validate() roundtrip
        $nie = 'Y0000000';
        // Compute expected letter
        $number = '10000000';
        $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $expected = $letters[(int)$number % 23];
        $this->assertTrue(NifValidator::validateNie($nie . $expected));
    }

    public function testValidNieZ(): void
    {
        $nie = 'Z0000000';
        $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $expected = $letters[(int)'20000000' % 23];
        $this->assertTrue(NifValidator::validateNie($nie . $expected));
    }

    public function testInvalidNieWrongLetter(): void
    {
        $this->assertFalse(NifValidator::validateNie('X1234567A'));
    }

    public function testInvalidNieWrongPrefixDigit(): void
    {
        // '1' is not a valid NIE prefix — should fail detectType → unknown
        $this->assertFalse(NifValidator::validate('11234567L'));
    }

    public function testNieNormalizedInput(): void
    {
        $this->assertTrue(NifValidator::validateNie('x 1234567 l'));
    }

    // -------------------------------------------------------------------------
    // CIF validation — entity types
    // -------------------------------------------------------------------------

    /**
     * @dataProvider cifAlwaysDigitProvider
     */
    public function testCifAlwaysDigitEntities(string $cif): void
    {
        $this->assertTrue(NifValidator::validateCif($cif), "CIF should be valid: $cif");
    }

    public static function cifAlwaysDigitProvider(): array
    {
        // Entity A, B, E, H → control must be digit.
        // All use digits 1234567: control_num = 4, so last char = '4'.
        return [
            ['A12345674'],
            ['B12345674'],
            ['E12345674'],
            ['H12345674'],
        ];
    }

    /**
     * @dataProvider cifAlwaysLetterProvider
     */
    public function testCifAlwaysLetterEntities(string $cif): void
    {
        $this->assertTrue(NifValidator::validateCif($cif), "CIF should be valid: $cif");
    }

    public static function cifAlwaysLetterProvider(): array
    {
        // Entity P, Q, K, L, M, S → control must be letter.
        // digits 1234567: control_num = 4, control_letter = 'JABCDEFGHI'[4] = 'D'.
        return [
            ['P1234567D'],
            ['Q1234567D'],
            ['K1234567D'],
            ['L1234567D'],
            ['M1234567D'],
            ['S1234567D'],
        ];
    }

    public function testCifEntityAcceptsBothLetterAndDigit(): void
    {
        // Entity C (always-either): digits 1234567, control_num=4, letter='D', digit='4'.
        $this->assertTrue(NifValidator::validateCif('C1234567D'));
        $this->assertTrue(NifValidator::validateCif('C12345674'));
    }

    public function testCifAlwaysDigitRejectsLetter(): void
    {
        // Entity A must use digit '4', not letter 'D'
        $this->assertFalse(NifValidator::validateCif('A1234567D'));
    }

    public function testCifAlwaysLetterRejectsDigit(): void
    {
        // Entity P must use letter 'E', not digit '4'
        $this->assertFalse(NifValidator::validateCif('P12345674'));
    }

    public function testCifInvalidEntityLetter(): void
    {
        // 'I' is not a valid CIF entity letter
        $this->assertFalse(NifValidator::validateCif('I1234567E'));
    }

    public function testCifInvalidControlChar(): void
    {
        $this->assertFalse(NifValidator::validateCif('B12345679'));
    }

    // -------------------------------------------------------------------------
    // validate() entry point
    // -------------------------------------------------------------------------

    public function testValidateNif(): void
    {
        $this->assertTrue(NifValidator::validate('12345678Z'));
    }

    public function testValidateNie(): void
    {
        $this->assertTrue(NifValidator::validate('X1234567L'));
    }

    public function testValidateCif(): void
    {
        $this->assertTrue(NifValidator::validate('B12345674'));
    }

    public function testValidatePassportAccepted(): void
    {
        $this->assertTrue(NifValidator::validate('ABC123456'));
    }

    public function testValidatePassportAcceptedWhenAllowPassportTrue(): void
    {
        $this->assertTrue(NifValidator::validate('ABC123456', true));
    }

    public function testValidatePassportRejectedWhenAllowPassportFalse(): void
    {
        $this->assertFalse(NifValidator::validate('ABC123456', false));
    }

    public function testValidateCifUnaffectedByAllowPassportFalse(): void
    {
        // allowPassport only applies to values detected as 'passport' — a real
        // CIF must still validate normally regardless of this flag.
        $this->assertTrue(NifValidator::validate('B12345674', false));
    }

    public function testValidateEmptyReturnsFalse(): void
    {
        $this->assertFalse(NifValidator::validate(''));
    }

    public function testValidateUnknownReturnsFalse(): void
    {
        $this->assertFalse(NifValidator::validate('NOT-VALID-AT-ALL'));
    }

    public function testValidateNifWithWrongLetterReturnsFalse(): void
    {
        $this->assertFalse(NifValidator::validate('12345678A'));
    }

    // -------------------------------------------------------------------------
    // Edge cases
    // -------------------------------------------------------------------------

    public function testNifWithLetterI(): void
    {
        // 'I' never appears in NIF_LETTERS table → any NIF ending in I is invalid
        $this->assertFalse(NifValidator::validate('00000000I'));
    }

    public function testNifWithLetterO(): void
    {
        $this->assertFalse(NifValidator::validate('00000000O'));
    }

    public function testNifWithLetterU(): void
    {
        $this->assertFalse(NifValidator::validate('00000000U'));
    }

    public function testCifWithEntityO(): void
    {
        // 'O' is not a valid CIF entity letter
        $this->assertFalse(NifValidator::validate('O1234567E'));
    }

    public function testAllZerosNif(): void
    {
        // 00000000 % 23 = 0 → 'T'
        $this->assertTrue(NifValidator::validate('00000000T'));
        $this->assertFalse(NifValidator::validate('00000000A'));
    }
}
