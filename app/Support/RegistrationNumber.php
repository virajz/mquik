<?php

namespace App\Support;

/**
 * One spelling for a number plate.
 *
 * People type "gj05ar1234", "GJ-05-AR-1234" and "GJ 05 AR 1234" for the same
 * car, and a plate typed one way must still find a plate stored the other.
 * Everything is stored in the spaced Indian form; comparisons squash the
 * spacing away entirely.
 */
class RegistrationNumber
{
    /**
     * "GJ 05 AR 1234" from anything recognisable.
     *
     * A plate that does not match the Indian pattern is returned uppercased and
     * trimmed rather than mangled — BH series, trade plates and imports exist,
     * and a wrong reformat is worse than none.
     */
    public static function format(?string $raw): ?string
    {
        $compact = self::compact($raw);

        if ($compact === null) {
            return null;
        }

        // State (2 letters) · RTO (1–2 digits) · series (0–3 letters) · number (1–4 digits)
        if (preg_match('/^([A-Z]{2})(\d{1,2})([A-Z]{0,3})(\d{1,4})$/', $compact, $m)) {
            return trim(implode(' ', array_filter([$m[1], $m[2], $m[3], $m[4]], fn ($p) => $p !== '')));
        }

        return $compact;
    }

    /** Spacing and punctuation removed — what two plates are compared on. */
    public static function compact(?string $raw): ?string
    {
        $clean = mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $raw));

        return $clean === '' ? null : $clean;
    }
}
