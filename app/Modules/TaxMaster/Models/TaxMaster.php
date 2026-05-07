<?php

namespace App\Modules\TaxMaster\Models;

use App\Concerns\Auditable;
use App\Modules\TaxMaster\Database\Factories\TaxMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'taxes';

    protected $guarded = [];

    protected $casts = [
        'gst_percent' => 'decimal:2',
        'cess_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): TaxMasterFactory
    {
        return TaxMasterFactory::new();
    }
}
