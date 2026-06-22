<?php

namespace App\Modules\TransportModeMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\TransportModeMaster\Database\Factories\TransportModeMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportModeMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'transport_modes';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): TransportModeMasterFactory
    {
        return TransportModeMasterFactory::new();
    }
}
