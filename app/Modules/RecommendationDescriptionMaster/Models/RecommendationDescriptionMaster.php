<?php

namespace App\Modules\RecommendationDescriptionMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\RecommendationCategoryMaster\Models\RecommendationCategoryMaster;
use App\Modules\RecommendationDescriptionMaster\Database\Factories\RecommendationDescriptionMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The wording a technician picks when recommending work on a checkpoint.
 * Filed under a Category and optionally a Sub Category so the picker stays short.
 */
class RecommendationDescriptionMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'recommendation_descriptions';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sequence_no' => 'integer',
    ];

    protected static array $searchableFields = ['name', 'code', 'category.name', 'subCategory.name'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(RecommendationCategoryMaster::class, 'category_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(RecommendationCategoryMaster::class, 'sub_category_id');
    }

    protected static function newFactory(): RecommendationDescriptionMasterFactory
    {
        return RecommendationDescriptionMasterFactory::new();
    }
}
