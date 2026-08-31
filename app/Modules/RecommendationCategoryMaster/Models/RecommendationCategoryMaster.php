<?php

namespace App\Modules\RecommendationCategoryMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\RecommendationCategoryMaster\Database\Factories\RecommendationCategoryMasterFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Category and Sub Category in one self-referencing table, the way
 * `inventory_groups` already works here: no parent means a Category, a parent
 * means a Sub Category of it.
 */
class RecommendationCategoryMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'recommendation_categories';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sequence_no' => 'integer',
    ];

    protected static array $searchableFields = ['name', 'code'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sequence_no')->orderBy('name');
    }

    /** Top-level entries — the Categories. */
    public function scopeCategories(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /** Everything filed under a Category — its Sub Categories. */
    public function scopeSubCategories(Builder $query, ?int $parentId = null): Builder
    {
        return $query->whereNotNull('parent_id')
            ->when($parentId, fn (Builder $q) => $q->where('parent_id', $parentId));
    }

    public function isSubCategory(): bool
    {
        return $this->parent_id !== null;
    }

    protected static function newFactory(): RecommendationCategoryMasterFactory
    {
        return RecommendationCategoryMasterFactory::new();
    }
}
