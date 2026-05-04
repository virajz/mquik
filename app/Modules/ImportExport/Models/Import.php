<?php

namespace App\Modules\ImportExport\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Import extends Model
{
    protected $guarded = [];

    protected $casts = [
        'mapping' => 'array',
        'behavior' => 'array',
        'dry_run' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    public function progressPercent(): int
    {
        if ($this->total_rows === 0) {
            return $this->isComplete() ? 100 : 0;
        }

        return (int) min(100, round(($this->processed_rows / $this->total_rows) * 100));
    }
}
