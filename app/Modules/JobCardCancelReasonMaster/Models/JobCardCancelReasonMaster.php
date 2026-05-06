<?php

namespace App\Modules\JobCardCancelReasonMaster\Models;

use App\Modules\JobCardCancelReasonMaster\Database\Factories\JobCardCancelReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCardCancelReasonMaster extends Model
{
    use HasFactory;

    protected $table = 'job_card_cancel_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): JobCardCancelReasonMasterFactory
    {
        return JobCardCancelReasonMasterFactory::new();
    }
}
