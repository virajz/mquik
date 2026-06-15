<?php

namespace App\Modules\JobCard\Models;

use App\Modules\DamageTypeMaster\Models\DamageTypeMaster;
use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobCardPhoto extends Model
{
    protected $table = 'job_card_photos';

    protected $guarded = [];

    protected $casts = [
        'size_bytes' => 'integer',
        'sequence_no' => 'integer',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function photoType(): BelongsTo
    {
        return $this->belongsTo(PhotoTypeMaster::class, 'photo_type_id');
    }

    public function damageType(): BelongsTo
    {
        return $this->belongsTo(DamageTypeMaster::class, 'damage_type_id');
    }
}
