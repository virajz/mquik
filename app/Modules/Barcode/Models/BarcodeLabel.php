<?php

namespace App\Modules\Barcode\Models;

use App\Modules\Barcode\Database\Factories\BarcodeLabelFactory;
use App\Modules\SpareMaster\Models\SpareMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarcodeLabel extends Model
{
    use HasFactory;

    public const TYPE_CODE128 = 'code128';

    public const TYPE_QR = 'qr';

    public const TYPE_EAN13 = 'ean13';

    protected $table = 'barcode_labels';

    protected $guarded = [];

    protected $casts = [
        'is_primary' => 'boolean',
        'copies' => 'integer',
    ];

    protected static function newFactory(): BarcodeLabelFactory
    {
        return BarcodeLabelFactory::new();
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    /**
     * Types available in the UI.
     *
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_CODE128 => 'Code 128',
            self::TYPE_QR => 'QR Code',
            self::TYPE_EAN13 => 'EAN-13',
        ];
    }

    /**
     * Label size presets.
     *
     * @return array<string, string>
     */
    public static function labelSizes(): array
    {
        return [
            '50x25' => '50 × 25 mm',
            '40x20' => '40 × 20 mm',
            '60x40' => '60 × 40 mm',
            '100x50' => '100 × 50 mm',
        ];
    }
}
