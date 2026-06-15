<?php

namespace App\Modules\PhotoTypeMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\PhotoTypeMaster\Database\Factories\PhotoTypeMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotoTypeMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'photo_types';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static array $searchableFields = ['name', 'code', 'group'];

    protected static function newFactory(): PhotoTypeMasterFactory
    {
        return PhotoTypeMasterFactory::new();
    }
}
