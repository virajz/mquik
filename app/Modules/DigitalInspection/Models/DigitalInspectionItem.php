<?php

namespace App\Modules\DigitalInspection\Models;

use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use App\Modules\RecommendationDescriptionMaster\Models\RecommendationDescriptionMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DigitalInspectionItem extends Model
{
    protected $table = 'digital_inspection_items';

    protected $guarded = [];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(DigitalInspection::class, 'digital_inspection_id');
    }

    public function inspectionItem(): BelongsTo
    {
        return $this->belongsTo(InspectionItemMaster::class, 'inspection_item_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(InspectionItemGroupMaster::class, 'inspection_item_group_id');
    }

    /**
     * The recommendation wording chosen for this checkpoint. Several apply to
     * one item — "replace pads" and "skim discs" is one job to a technician.
     */
    public function recommendationDescriptions(): BelongsToMany
    {
        return $this->belongsToMany(
            RecommendationDescriptionMaster::class,
            'digital_inspection_item_recommendations',
            'digital_inspection_item_id',
            'recommendation_description_id',
        )->withPivot('sequence_no')->withTimestamps()->orderBy('digital_inspection_item_recommendations.sequence_no');
    }
}
