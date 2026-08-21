<?php

namespace App\Modules\WorkshopDepartmentMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\DepartmentMaster\Models\DepartmentMaster;
use App\Modules\WorkshopDepartmentMaster\Database\Factories\WorkshopDepartmentMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkshopDepartmentMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'workshop_departments';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): WorkshopDepartmentMasterFactory
    {
        return WorkshopDepartmentMasterFactory::new();
    }

    /**
     * Mirror into the HR department list.
     *
     * One list to maintain: adding or renaming a workshop department creates or
     * points at the matching HR department, so staff can be attached to the same
     * department a job card routes to. HR keeps its own extra departments
     * (accounts, stores, admin) — this only guarantees the workshop ones exist
     * on both sides.
     */
    protected static function booted(): void
    {
        static::saving(function (self $row) {
            $name = mb_strtoupper(trim((string) $row->name));

            if ($name === '') {
                return;
            }

            $row->name = $name;

            $department = DepartmentMaster::whereRaw('upper(name) = ?', [$name])->first()
                ?? DepartmentMaster::create(['name' => $name, 'is_active' => true]);

            $row->department_id = $department->id;
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(DepartmentMaster::class, 'department_id');
    }
}
