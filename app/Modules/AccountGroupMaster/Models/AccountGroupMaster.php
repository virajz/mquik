<?php

namespace App\Modules\AccountGroupMaster\Models;

use App\Concerns\Auditable;
use App\Modules\AccountGroupMaster\Database\Factories\AccountGroupMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountGroupMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'account_groups';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): AccountGroupMasterFactory
    {
        return AccountGroupMasterFactory::new();
    }
}
