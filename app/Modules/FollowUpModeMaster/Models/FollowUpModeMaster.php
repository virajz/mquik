<?php

namespace App\Modules\FollowUpModeMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\FollowUpModeMaster\Database\Factories\FollowUpModeMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUpModeMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'follow_up_modes';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): FollowUpModeMasterFactory
    {
        return FollowUpModeMasterFactory::new();
    }
}
