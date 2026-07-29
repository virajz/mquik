<?php

namespace App\Modules\VehicleMovement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleMovementAttachment extends Model
{
    protected $table = 'vehicle_movement_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'entry_photo' => 'Entry Photo',
            'exit_photo' => 'Exit Photo',
            'number_plate' => 'Number Plate',
            'other' => 'Other',
        ];
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(VehicleMovement::class, 'vehicle_movement_id');
    }
}
