<?php

namespace App\Modules\VendorPurchaseInquiry\Support;

use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use Illuminate\Support\Facades\Storage;

/**
 * Builds the message a vendor is sent for an RFQ.
 *
 * The workshop's rule is that a vendor cannot identify the right part from a
 * description alone — they need the VIN (and usually a photo of the part or the
 * plate). This assembles that into a copy-paste message plus a list of image
 * paths, so it can go out over WhatsApp, email, or be read down the phone.
 *
 * Nothing here sends anything. There is no messaging integration in this
 * application yet; this produces the payload and the UI lets a human send it.
 */
class InquiryDispatchPayload
{
    /**
     * @return array{message: string, vin: ?string, registration_no: ?string, images: list<array{path: string, url: ?string, label: string}>}
     */
    public static function for(VendorPurchaseInquiry $inquiry): array
    {
        $inquiry->loadMissing(['items', 'attachments', 'jobCard.customerVehicle.variant.model.brand']);

        $vehicle = $inquiry->jobCard?->customerVehicle;
        $vin = $vehicle?->vin;

        $vehicleLine = trim(collect([
            $vehicle?->variant?->model?->brand?->name,
            $vehicle?->variant?->model?->name,
            $vehicle?->variant?->name,
        ])->filter()->implode(' '));

        $lines = [];
        $lines[] = 'Parts inquiry '.($inquiry->vpi_no ?: '');

        if ($vehicleLine !== '') {
            $lines[] = 'Vehicle: '.$vehicleLine;
        }
        if ($vehicle?->registration_no) {
            $lines[] = 'Reg No: '.$vehicle->registration_no;
        }

        // The VIN is the whole point of the message — call it out even when absent
        // so whoever sends it knows to go and find it.
        $lines[] = 'VIN: '.($vin ?: 'NOT RECORDED — please confirm before quoting');

        $lines[] = '';
        $lines[] = 'Parts required:';

        foreach ($inquiry->items as $i => $item) {
            $qty = rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.');
            $lines[] = ($i + 1).'. '.$item->description.' — qty '.$qty;
        }

        $lines[] = '';
        $lines[] = 'Please confirm price, brand, warranty and delivery time.';

        return [
            'message' => implode("\n", $lines),
            'vin' => $vin,
            'registration_no' => $vehicle?->registration_no,
            'images' => self::images($inquiry),
        ];
    }

    /**
     * Image attachments on the inquiry, with a public URL where one can be made.
     *
     * @return list<array{path: string, url: ?string, label: string}>
     */
    protected static function images(VendorPurchaseInquiry $inquiry): array
    {
        return $inquiry->attachments
            ->filter(fn ($a) => $a->path && self::looksLikeImage($a->path))
            ->map(fn ($a) => [
                'path' => $a->path,
                'url' => Storage::disk('public')->exists($a->path)
                    ? Storage::disk('public')->url($a->path)
                    : null,
                'label' => $a->original_name ?: basename($a->path),
            ])
            ->values()
            ->all();
    }

    protected static function looksLikeImage(string $path): bool
    {
        return in_array(
            strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            ['jpg', 'jpeg', 'png', 'webp', 'heic'],
            true,
        );
    }
}
