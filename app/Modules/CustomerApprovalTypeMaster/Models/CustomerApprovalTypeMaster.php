<?php

namespace App\Modules\CustomerApprovalTypeMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerApprovalTypeMaster\Database\Factories\CustomerApprovalTypeMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerApprovalTypeMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'customer_approval_types';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): CustomerApprovalTypeMasterFactory
    {
        return CustomerApprovalTypeMasterFactory::new();
    }
}
