<?php

namespace App\Modules\VehicleAmc\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleAmcAttachment extends Model
{
    protected $table = 'vehicle_amc_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'signed_agreement' => 'Signed AMC Agreement',
            'advisor_note' => 'Advisor Note',
            'customer_note' => 'Customer Note',
        ];
    }

    public function amc(): BelongsTo
    {
        return $this->belongsTo(VehicleAmc::class, 'vehicle_amc_id');
    }
}
