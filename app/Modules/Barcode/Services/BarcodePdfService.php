<?php

namespace App\Modules\Barcode\Services;

use App\Modules\Barcode\Models\BarcodeLabel;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BarcodePdfService
{
    public const TTL_HOURS = 24;

    /**
     * Return the local-disk path of the PDF for this label, generating it if absent or stale.
     */
    public static function path(BarcodeLabel $label): string
    {
        $disk = Storage::disk('local');
        $path = self::diskPath($label);

        $expired = $disk->exists($path)
            && now()->diffInHours(Carbon::createFromTimestamp($disk->lastModified($path))) > self::TTL_HOURS;

        if (! $disk->exists($path) || $expired) {
            $pdf = self::build($label);
            $disk->put($path, $pdf);
        }

        return $path;
    }

    /**
     * Stream the PDF as an HTTP response.
     */
    public static function response(BarcodeLabel $label): StreamedResponse
    {
        $path = self::path($label);
        $filename = 'label-'.$label->barcode.'.pdf';

        return Storage::disk('local')->response($path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Render the PDF bytes for a label.
     */
    private static function build(BarcodeLabel $label): string
    {
        $label->loadMissing('spare');

        [$widthMm, $heightMm] = self::parseLabelSize($label->label_size);

        $barcodeDataUri = self::barcodeDataUri(
            $label->barcode,
            $label->barcode_type,
            $heightMm,
        );

        $pdf = Pdf::loadView('pdf.barcode-label', [
            'barcodeValue' => $label->barcode,
            'barcodeDataUri' => $barcodeDataUri,
            'widthMm' => $widthMm,
            'heightMm' => $heightMm,
            'copies' => max(1, (int) $label->copies),
        ]);

        $pdf->setPaper([0, 0, self::mmToPoints($widthMm), self::mmToPoints($heightMm)]);

        return $pdf->output();
    }

    /**
     * Generate a base64 data URI for the barcode image (PNG).
     * Code128 / EAN-13 use the picqer barcode generator at high DPI for crisp bars;
     * QR uses bacon/bacon-qr-code with the Imagick backend.
     */
    private static function barcodeDataUri(
        string $value,
        string $type,
        float $heightMm,
    ): string {
        $type = strtolower($type);

        if ($type === 'qr') {
            return self::qrDataUri($value, $heightMm);
        }

        // High-DPI: target ~300 DPI (12 px/mm). Multiplier of 4 with extra height
        // gives crisp barcode bars that scale cleanly inside the label.
        $pxHeight = (int) round($heightMm * 12 * 0.6);

        $barcodeType = $type === 'ean13'
            ? BarcodeGeneratorPNG::TYPE_EAN_13
            : BarcodeGeneratorPNG::TYPE_CODE_128;

        $png = (new BarcodeGeneratorPNG)->getBarcode($value, $barcodeType, 4, $pxHeight);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * Render a QR code as a PNG data URI. Square size based on label height.
     */
    private static function qrDataUri(string $value, float $heightMm): string
    {
        // High-DPI square QR — 300 DPI equivalent.
        $pxSize = (int) round($heightMm * 12 * 0.85);

        $renderer = new ImageRenderer(
            new RendererStyle($pxSize, 0),
            new ImagickImageBackEnd('png'),
        );

        $writer = new Writer($renderer);
        $png = $writer->writeString($value);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    private static function diskPath(BarcodeLabel $label): string
    {
        $hash = md5($label->barcode.'|'.$label->label_size.'|'.$label->barcode_type.'|'.$label->copies);

        return 'barcode-pdfs/'.$label->id.'-'.$hash.'.pdf';
    }

    /**
     * Parse "50x25" → [50.0, 25.0]
     *
     * @return array{0: float, 1: float}
     */
    private static function parseLabelSize(string $size): array
    {
        $parts = explode('x', $size);

        return [(float) ($parts[0] ?? 50), (float) ($parts[1] ?? 25)];
    }

    private static function mmToPoints(float $mm): float
    {
        return $mm * 2.8346;  // 1mm = 2.8346pt
    }
}
