<?php

namespace App\Modules\Barcode\Services;

use App\Modules\Barcode\Models\BarcodeLabel;
use App\Modules\SpareMaster\Models\SpareMaster;

class BarcodeService
{
    /**
     * Generate an internal barcode string for a spare.
     * Code128: MQ + 7-digit spare ID + 1 check digit  (e.g. MQ00000421)
     * EAN-13:  pure numeric 12 digits + EAN check digit (e.g. 690000000042X)
     */
    public static function generate(int $spareId, string $type = 'code128'): string
    {
        if (strtolower($type) === 'ean13') {
            return self::generateEan13($spareId);
        }

        $body = 'MQ'.str_pad((string) $spareId, 7, '0', STR_PAD_LEFT);
        $check = self::luhnCheckDigit($body);

        return $body.$check;
    }

    /**
     * Generate a valid EAN-13 string for a spare ID.
     * Prefix 690 (internal use) + 8-digit spare ID padded + EAN check digit.
     */
    public static function generateEan13(int $spareId): string
    {
        // 12 digits: prefix 690 + spare ID padded to 9 digits
        $body = '690'.str_pad((string) $spareId, 9, '0', STR_PAD_LEFT);
        $check = self::ean13CheckDigit($body);

        return $body.$check;
    }

    private static function ean13CheckDigit(string $twelveDigits): string
    {
        $sum = 0;
        foreach (str_split($twelveDigits) as $i => $digit) {
            $sum += (int) $digit * ($i % 2 === 0 ? 1 : 3);
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }

    /**
     * Ensure a spare has a primary BarcodeLabel, creating one if absent.
     */
    public static function ensurePrimary(SpareMaster $spare): BarcodeLabel
    {
        $existing = BarcodeLabel::where('spare_id', $spare->id)
            ->where('is_primary', true)
            ->first();

        if ($existing) {
            return $existing;
        }

        return BarcodeLabel::create([
            'spare_id' => $spare->id,
            'barcode' => self::generate($spare->id),
            'barcode_type' => BarcodeLabel::TYPE_CODE128,
            'label_size' => '50x25',
            'copies' => 1,
            'is_primary' => true,
        ]);
    }

    /**
     * Resolve a scanned barcode string to a spare (returns null if not found).
     */
    public static function resolve(string $barcode): ?SpareMaster
    {
        $label = BarcodeLabel::where('barcode', $barcode)->with('spare')->first();

        return $label?->spare;
    }

    /**
     * Simple Luhn-style single check digit (mod-10).
     */
    private static function luhnCheckDigit(string $body): string
    {
        $sum = 0;
        $double = true;
        foreach (array_reverse(str_split($body)) as $char) {
            $digit = is_numeric($char) ? (int) $char : (ord($char) % 10);
            if ($double) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
            $double = ! $double;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }
}
