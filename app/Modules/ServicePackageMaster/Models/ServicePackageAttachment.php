<?php

namespace App\Modules\ServicePackageMaster\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePackageAttachment extends Model
{
    protected $table = 'service_package_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'brochure' => 'Package Brochure',
            'package_note' => 'Package Note',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackageMaster::class, 'service_package_id');
    }
}
