<?php

namespace App\Modules\LossTypeMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\LossTypeMaster\Database\Factories\LossTypeMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LossTypeMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'loss_types';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): LossTypeMasterFactory
    {
        return LossTypeMasterFactory::new();
    }
}
