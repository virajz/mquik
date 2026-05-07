<?php

namespace App\Modules\BankMaster\Models;

use App\Concerns\Auditable;
use App\Modules\BankMaster\Database\Factories\BankMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'banks';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): BankMasterFactory
    {
        return BankMasterFactory::new();
    }
}
