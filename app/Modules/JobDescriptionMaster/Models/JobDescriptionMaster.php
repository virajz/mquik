<?php

namespace App\Modules\JobDescriptionMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\JobDescriptionMaster\Database\Factories\JobDescriptionMasterFactory;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobDescriptionMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'job_descriptions';

    protected $guarded = [];

    protected $casts = [
        'standard_hours' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    public static function categories(): array
    {
        return ['frequent', 'general'];
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    protected static function newFactory(): JobDescriptionMasterFactory
    {
        return JobDescriptionMasterFactory::new();
    }
}
