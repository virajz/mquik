<?php

namespace App\Modules\CustomerMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Database\Factories\CustomerMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'customers';

    protected $guarded = [];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'phone', 'email', 'city'];

    public function toSearchResult(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->name,
            'subtitle' => $this->phone ? '+91 '.$this->phone : null,
        ];
    }

    protected static function newFactory(): CustomerMasterFactory
    {
        return CustomerMasterFactory::new();
    }
}
