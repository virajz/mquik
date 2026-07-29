<?php

namespace App\Modules\ProformaApproval\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One verification checkpoint an approver (billing / store / advisor / admin)
 * ticks OK or flags on the proforma.
 */
class ProformaApprovalCheckpoint extends Model
{
    protected $table = 'proforma_approval_checkpoints';

    protected $guarded = [];

    protected $casts = ['sequence_no' => 'integer'];

    public function approval(): BelongsTo
    {
        return $this->belongsTo(ProformaApproval::class, 'proforma_approval_id');
    }
}
