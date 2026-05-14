<?php

namespace App\Modules\JobCard\Models;

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
}
