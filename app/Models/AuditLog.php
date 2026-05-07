<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public $timestamps = false; // only created_at via migration default

    protected $guarded = [];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Diff: array of [field => ['old' => ..., 'new' => ...]] excluding equal/timestamps.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function diff(): array
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];
        $skip = ['updated_at', 'created_at'];

        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $out = [];
        foreach ($keys as $k) {
            if (in_array($k, $skip, true)) {
                continue;
            }
            $o = $old[$k] ?? null;
            $n = $new[$k] ?? null;
            if ($o === $n) {
                continue;
            }
            $out[$k] = ['old' => $o, 'new' => $n];
        }

        return $out;
    }
}
