<?php

namespace App\Modules\CustomerMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\BusinessTypeMaster\Models\BusinessTypeMaster;
use App\Modules\CustomerMaster\Database\Factories\CustomerMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CustomerMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'customers';

    protected $guarded = [];

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessTypeMaster::class, 'business_type_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    public function primaryAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class, 'customer_id')->where('is_primary', true);
    }

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'phone', 'email', 'aadhar', 'pan'];

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
