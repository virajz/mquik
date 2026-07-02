<?php

namespace App\Modules\Proforma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProformaAttachment extends Model
{
    protected $table = 'proforma_attachments';

    protected $guarded = [];

    public function proforma(): BelongsTo
    {
        return $this->belongsTo(Proforma::class, 'proforma_id');
    }
}
