<?php

namespace App\Modules\DesignationMaster\Models;

use App\Modules\DesignationMaster\Database\Factories\DesignationMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DesignationMaster extends Model
{
    use HasFactory;

    protected $table = 'designations';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): DesignationMasterFactory
    {
        return DesignationMasterFactory::new();
    }
}
