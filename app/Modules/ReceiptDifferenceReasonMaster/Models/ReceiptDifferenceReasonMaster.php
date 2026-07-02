<?php

namespace App\Modules\ReceiptDifferenceReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ReceiptDifferenceReasonMaster\Database\Factories\ReceiptDifferenceReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptDifferenceReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'receipt_difference_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): ReceiptDifferenceReasonMasterFactory
    {
        return ReceiptDifferenceReasonMasterFactory::new();
    }
}
