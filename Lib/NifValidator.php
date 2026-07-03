<?php
declare(strict_types=1);

namespace FacturaScripts\Plugins\WidgetNif\Lib;

/**
 * Spanish fiscal identification number validator.
 *
 * Supports:
 *   NIF  — 8 digits + check letter (modulo 23 algorithm).
 *   NIE  — X/Y/Z prefix + 7 digits + check letter (prefix substituted, then NIF algorithm).
 *   CIF  — Entity letter (A-W) + 7 digits + control char (letter or digit, entity-dependent).
 *   Passport — basic pattern only ([A-Z0-9]{6,12}), no algorithmic check.
 *
 * This class has no FacturaScripts dependencies and can be used standalone.
 */
class NifValidator
{
    /** Letter table for NIF/NIE modulo-23 algorithm. */
    private const NIF_LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';

    /** Control-character letter table for CIF (indexed 0-9). */
    private const CIF_CONTROL_LETTERS = 'JABCDEFGHI';

    /** CIF entity types whose control character is ALWAYS a letter. */
    private const CIF_ALWAYS_LETTER = ['P', 'Q', 'K', 'L', 'M', 'S'];

    /** CIF entity types whose control character is ALWAYS a digit. */
    private const CIF_ALWAYS_DIGIT = ['A', 'B', 'E', 'H'];

    /**
     * Normalizes input: trim + uppercase + remove spaces, dashes, dots.
     */
    public static function normalize(string $value): string
    {
        return strtoupper(trim(str_replace([' ', '-', '.'], '', $value)));
    }

    /**
     * Detects document type from a normalized value.
     *
     * @return string 'nif'|'nie'|'cif'|'passport'|'unknown'
     */
    public static function detectType(string $value): string
    {
        $v = self::normalize($value);

        if (preg_match('/^[0-9]{8}[A-Z]$/', $v)) {
            return 'nif';
        }

        if (preg_match('/^[XYZ][0-9]{7}[A-Z]$/', $v)) {
            return 'nie';
        }

        // Valid CIF entity letters exclude I, O, T, X, Y, Z.
        if (preg_match('/^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[0-9A-J]$/', $v)) {
            return 'cif';
        }

        // Structurally matches CIF (letter + 7 digits + CIF-valid control char) but with an
        // invalid entity letter (e.g. 'O' instead of '0'). Return 'unknown' to avoid
        // misclassifying a likely typo as a passport.
        if (preg_match('/^[A-Z][0-9]{7}[0-9A-J]$/', $v)
            && !preg_match('/^[ABCDEFGHJKLMNPQRSUVW]/', $v)) {
            return 'unknown';
        }

        if (preg_match('/^[A-Z0-9]{6,12}$/', $v)) {
            return 'passport';
        }

        return 'unknown';
    }

    /**
     * Validates a NIF (8 digits + check letter, modulo 23).
     *
     * Excluded letters I, O, U, Ñ — not present in the NIF_LETTERS table.
     */
    public static function validateNif(string $value): bool
    {
        $v = self::normalize($value);

        if (!preg_match('/^([0-9]{8})([A-Z])$/', $v, $m)) {
            return false;
        }

        $expected = self::NIF_LETTERS[(int)$m[1] % 23];
        return $m[2] === $expected;
    }

    /**
     * Validates a NIE (X/Y/Z + 7 digits + check letter).
     *
     * Prefix substitution: X→0, Y→1, Z→2. Then apply NIF modulo-23 algorithm.
     */
    public static function validateNie(string $value): bool
    {
        $v = self::normalize($value);

        if (!preg_match('/^([XYZ])([0-9]{7})([A-Z])$/', $v, $m)) {
            return false;
        }

        $prefix = ['X' => '0', 'Y' => '1', 'Z' => '2'][$m[1]];
        $expected = self::NIF_LETTERS[(int)($prefix . $m[2]) % 23];

        return $m[3] === $expected;
    }

    /**
     * Validates a CIF (entity letter + 7 digits + control char).
     *
     * Control char is a letter (JABCDEFGHI) or digit (0-9) depending on entity type:
     *   Always letter : P, Q, K, L, M, S
     *   Always digit  : A, B, E, H
     *   Either        : C, D, F, G, J, N, R, U, V, W
     */
    public static function validateCif(string $value): bool
    {
        $v = self::normalize($value);

        if (!preg_match('/^([ABCDEFGHJKLMNPQRSUVW])([0-9]{7})([0-9A-J])$/', $v, $m)) {
            return false;
        }

        [$entity, $digits, $ctrl] = [$m[1], $m[2], $m[3]];
        $ctrlNum = self::cifControlNumber($digits);
        $ctrlLetter = self::CIF_CONTROL_LETTERS[$ctrlNum];
        $ctrlDigit = (string)$ctrlNum;

        if (in_array($entity, self::CIF_ALWAYS_LETTER, true)) {
            return $ctrl === $ctrlLetter;
        }

        if (in_array($entity, self::CIF_ALWAYS_DIGIT, true)) {
            return $ctrl === $ctrlDigit;
        }

        return $ctrl === $ctrlLetter || $ctrl === $ctrlDigit;
    }

    /**
     * Entry point: detects type and validates.
     *
     * Passports are accepted by pattern only (no algorithmic check) unless
     * $allowPassport is false, in which case any value detected as 'passport'
     * is rejected. Useful for businesses that never deal with foreign
     * documents and want the field restricted to Spanish NIF/NIE/CIF only.
     *
     * Note: this parameter only affects THIS PHP call. The widget's XML
     * attribute (if used) only drives the client-side visual feedback — it
     * cannot reach the consumer model's test() automatically. Call this
     * method with $allowPassport = false explicitly from your model if you
     * need it enforced server-side.
     */
    public static function validate(string $value, bool $allowPassport = true): bool
    {
        if (empty($value)) {
            return false;
        }

        switch (self::detectType($value)) {
            case 'nif':
                return self::validateNif($value);
            case 'nie':
                return self::validateNie($value);
            case 'cif':
                return self::validateCif($value);
            case 'passport':
                return $allowPassport;
            default:
                return false;
        }
    }

    /**
     * Calculates CIF control number from the 7-digit string.
     *
     * Odd positions (1,3,5,7 — 0-indexed 0,2,4,6): multiply digit by 2; if ≥10 sum its digits.
     * Even positions (2,4,6 — 0-indexed 1,3,5): add digit directly.
     * Result = (10 - (total mod 10)) mod 10.
     */
    private static function cifControlNumber(string $digits): int
    {
        $sum = 0;

        for ($i = 0; $i < 7; $i++) {
            $d = (int)$digits[$i];

            if ($i % 2 === 0) {
                // Odd 1-indexed position: multiply by 2, then digit-sum if ≥ 10.
                $doubled = $d * 2;
                $sum += $doubled >= 10 ? ($doubled - 9) : $doubled;
            } else {
                $sum += $d;
            }
        }

        return (10 - ($sum % 10)) % 10;
    }
}
