<?php

namespace App\Modules\InsuranceCompanyMaster\Models;

use App\Concerns\Searchable;
use App\Modules\InsuranceCompanyMaster\Database\Factories\InsuranceCompanyMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceCompanyMaster extends Model
{
    use HasFactory;
    use Searchable;

    protected $table = 'insurance_companies';

    protected $guarded = [];

    protected $casts = [
        'default_pass_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'short_name', 'gstin', 'contact_person', 'phone', 'email'];

    public function toSearchResult(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->name,
            'subtitle' => $this->short_name,
        ];
    }

    protected static function newFactory(): InsuranceCompanyMasterFactory
    {
        return InsuranceCompanyMasterFactory::new();
    }
}
