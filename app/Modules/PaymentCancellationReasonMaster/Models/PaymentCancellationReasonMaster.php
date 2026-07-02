<?php

namespace App\Modules\PaymentCancellationReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\PaymentCancellationReasonMaster\Database\Factories\PaymentCancellationReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentCancellationReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'payment_cancellation_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): PaymentCancellationReasonMasterFactory
    {
        return PaymentCancellationReasonMasterFactory::new();
    }
}
