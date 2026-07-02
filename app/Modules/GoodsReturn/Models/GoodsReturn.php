<?php

namespace App\Modules\GoodsReturn\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ChallanEntry\Models\Challan;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\CreditNoteReasonMaster\Models\CreditNoteReasonMaster;
use App\Modules\GoodsReturn\Database\Factories\GoodsReturnFactory;
use App\Modules\PurchaseEntry\Models\PurchaseEntry;
use App\Modules\TransportModeMaster\Models\TransportModeMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReturn extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'goods_returns';

    protected $guarded = [];

    protected $casts = [
        'returned_at' => 'date',
        'parts_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    protected static array $searchableFields = ['return_no', 'grn_reference', 'notes'];

    protected static function newFactory(): GoodsReturnFactory
    {
        return GoodsReturnFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->return_no === null) {
                $row->forceFill([
                    'return_no' => 'GR-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function creditNoteReason(): BelongsTo
    {
        return $this->belongsTo(CreditNoteReasonMaster::class, 'credit_note_reason_id');
    }

    public function transportMode(): BelongsTo
    {
        return $this->belongsTo(TransportModeMaster::class, 'transport_mode_id');
    }

    public function transportCompany(): BelongsTo
    {
        return $this->belongsTo(CourierCompanyMaster::class, 'transport_company_id');
    }

    public function purchaseEntry(): BelongsTo
    {
        return $this->belongsTo(PurchaseEntry::class, 'purchase_entry_id');
    }

    public function challan(): BelongsTo
    {
        return $this->belongsTo(Challan::class, 'challan_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReturnItem::class, 'goods_return_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(GoodsReturnAttachment::class, 'goods_return_id');
    }

    /** @return array<string, string> */
    public static function documentTypes(): array
    {
        return [
            'credit_note' => 'Credit Note',
            'debit_note' => 'Debit Note',
        ];
    }

    /** @return array<string, string> */
    public static function creditNoteTypes(): array
    {
        return [
            'e_credit' => 'E-Credit Note',
            'tax_credit' => 'Tax Credit Note',
            'bill_of_supply' => 'Bill of Supply',
        ];
    }

    /** @return array<string, string> */
    public static function warrantyTypes(): array
    {
        return [
            'no_warranty' => 'No Warranty',
            'vendor' => 'Vendor Warranty',
            'manufacturer' => 'Manufacturer Warranty',
        ];
    }

    /** @return array<string, string> */
    public static function warrantyPeriods(): array
    {
        return [
            '3m' => '3 Months',
            '6m' => '6 Months',
            '12m' => '12 Months',
            '24m' => '24 Months',
        ];
    }

    /** @return array<string, string> */
    public static function materialConditions(): array
    {
        return [
            'new' => 'New',
            'used' => 'Used',
            'unused' => 'Unused',
            'open_box' => 'Open Box',
        ];
    }
}
