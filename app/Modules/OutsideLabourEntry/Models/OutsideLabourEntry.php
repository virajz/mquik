<?php

namespace App\Modules\OutsideLabourEntry\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\LossReasonMaster\Models\LossReasonMaster;
use App\Modules\OutsideLabourEntry\Database\Factories\OutsideLabourEntryFactory;
use App\Modules\TransportModeMaster\Models\TransportModeMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutsideLabourEntry extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'outside_labour_entries';

    protected $guarded = [];

    protected $casts = [
        'invoice_date' => 'date',
        'parts_total' => 'decimal:2',
        'labour_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    protected static array $searchableFields = ['entry_no', 'invoice_no', 'notes', 'vendor.name'];

    protected static function newFactory(): OutsideLabourEntryFactory
    {
        return OutsideLabourEntryFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->entry_no === null) {
                $row->forceFill([
                    'entry_no' => 'OLE-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function vendorType(): BelongsTo
    {
        return $this->belongsTo(VendorTypeMaster::class, 'vendor_type_id');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'department_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'technician_id');
    }

    public function lossReason(): BelongsTo
    {
        return $this->belongsTo(LossReasonMaster::class, 'loss_reason_id');
    }

    public function transportMode(): BelongsTo
    {
        return $this->belongsTo(TransportModeMaster::class, 'transport_mode_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OutsideLabourEntryItem::class, 'outside_labour_entry_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OutsideLabourEntryAttachment::class, 'outside_labour_entry_id');
    }

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'invoice' => 'Invoice',
            'quote' => 'Quote',
            'work_order' => 'Work Order',
            'report' => 'Report',
            'before_photo' => 'Before Photos',
            'after_photo' => 'After Photos',
        ];
    }
}
