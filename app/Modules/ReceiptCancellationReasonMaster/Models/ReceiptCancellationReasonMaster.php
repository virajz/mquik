<?php

namespace App\Modules\ReceiptCancellationReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ReceiptCancellationReasonMaster\Database\Factories\ReceiptCancellationReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptCancellationReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'receipt_cancellation_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): ReceiptCancellationReasonMasterFactory
    {
        return ReceiptCancellationReasonMasterFactory::new();
    }
}
