<?php

namespace App\Modules\PaymentModeMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\PaymentModeMaster\Database\Factories\PaymentModeMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentModeMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'payment_modes';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): PaymentModeMasterFactory
    {
        return PaymentModeMasterFactory::new();
    }
}
