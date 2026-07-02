<?php

namespace App\Modules\InvoiceCancellationReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\InvoiceCancellationReasonMaster\Database\Factories\InvoiceCancellationReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceCancellationReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'invoice_cancellation_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): InvoiceCancellationReasonMasterFactory
    {
        return InvoiceCancellationReasonMasterFactory::new();
    }
}
