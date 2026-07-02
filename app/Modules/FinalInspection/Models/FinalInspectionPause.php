<?php

namespace App\Modules\FinalInspection\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalInspectionPause extends Model
{
    protected $table = 'final_inspection_pauses';

    protected $guarded = [];

    protected $casts = [
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
    ];

    public function finalInspection(): BelongsTo
    {
        return $this->belongsTo(FinalInspection::class, 'final_inspection_id');
    }
}
