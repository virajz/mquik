<?php

namespace App\Modules\TyreReport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One wheel's measurements within a tyre report. */
class TyreReportLine extends Model
{
    protected $table = 'tyre_report_lines';

    protected $guarded = [];

    protected $casts = [
        'pressure_psi' => 'decimal:1',
        'tread_depth_mm' => 'decimal:1',
        'has_crack' => 'boolean',
        'has_bulge' => 'boolean',
        'is_worn_out' => 'boolean',
        'has_puncture' => 'boolean',
        'sequence_no' => 'integer',
    ];

    public function tyreReport(): BelongsTo
    {
        return $this->belongsTo(TyreReport::class);
    }

    public function positionLabel(): string
    {
        return TyreReport::POSITIONS[$this->position] ?? $this->position;
    }

    /**
     * Percentage worn, from the sidewall guide on the paper report:
     * 8 mm = new, 1.6 mm = the legal limit (fully worn).
     */
    public function wornPercent(): ?int
    {
        if ($this->tread_depth_mm === null) {
            return null;
        }

        $depth = (float) $this->tread_depth_mm;
        $new = 8.0;
        $limit = 1.6;

        $worn = (int) round((($new - $depth) / ($new - $limit)) * 100);

        return max(0, min(100, $worn));
    }
}
