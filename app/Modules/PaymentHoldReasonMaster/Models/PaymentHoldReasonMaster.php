<?php

namespace App\Modules\PaymentHoldReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\PaymentHoldReasonMaster\Database\Factories\PaymentHoldReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentHoldReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'payment_hold_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): PaymentHoldReasonMasterFactory
    {
        return PaymentHoldReasonMasterFactory::new();
    }
}
