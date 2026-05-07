<?php

namespace App\Modules\EnquirySourceMaster\Models;

use App\Concerns\Auditable;
use App\Modules\EnquirySourceMaster\Database\Factories\EnquirySourceMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnquirySourceMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'enquiry_sources';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): EnquirySourceMasterFactory
    {
        return EnquirySourceMasterFactory::new();
    }
}
