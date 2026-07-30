<?php

namespace App\Modules\VendorMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\GstTypeMaster\Models\GstTypeMaster;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\RegionMaster\Models\RegionMaster;
use App\Modules\ServiceSpecialistMaster\Models\ServiceSpecialistMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\VendorMaster\Database\Factories\VendorMasterFactory;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class VendorMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'vendors';

    protected static array $searchableFields = ['name', 'vendor_code', 'phone', 'email', 'gstin'];

    public function toSearchResult(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->name,
            'subtitle' => $this->vendor_code.($this->phone ? ' • +91 '.$this->phone : ''),
        ];
    }

    protected $guarded = [];

    protected $casts = [
        'credit_days' => 'integer',
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
        'registration_date' => 'date',
        'rating' => 'integer',
        'on_time_delivery_percent' => 'decimal:2',
        'parts_return_percent' => 'decimal:2',
        'return_rejection_percent' => 'decimal:2',
        'avg_response_hours' => 'decimal:2',
    ];

    /** @return array<string, string> */
    public static function classifications(): array
    {
        return [
            'manufacturer' => 'Manufacturer',
            'authorized_distributor' => 'Authorized Distributor',
            'dealer' => 'Dealer',
            'wholesaler' => 'Wholesaler',
            'retailer' => 'Retailer',
            'importer' => 'Importer',
        ];
    }

    /** @return array<string, string> */
    public static function constitutions(): array
    {
        return [
            'sole_proprietorship' => 'Sole Proprietorship',
            'partnership' => 'Partnership',
            'llp' => 'Limited Liability Partnership',
            'private_limited' => 'Private Limited Company',
        ];
    }

    /** @return array<string, string> */
    public static function gstRegistrationTypes(): array
    {
        return [
            'regular' => 'Regular',
            'sez' => 'SEZ',
            'export' => 'Export',
            'composition' => 'Composition',
            'unregistered' => 'Unregistered',
        ];
    }

    /** @return array<string, string> */
    public static function msmeTypes(): array
    {
        return ['micro' => 'Micro', 'small' => 'Small', 'medium' => 'Medium', 'enterprise' => 'Enterprise'];
    }

    /** @return array<string, string> */
    public static function msmeActivities(): array
    {
        return ['trading' => 'Trading', 'services' => 'Services', 'manufacturing' => 'Manufacturing'];
    }

    /** @return array<string, string> */
    public static function vendorCategories(): array
    {
        return [
            'genuine' => 'Genuine Parts Supplier',
            'oem' => 'OEM Parts Supplier',
            'aftermarket' => 'Aftermarket Parts Supplier',
            'scrap_refurbished' => 'Scrap / Refurbished Parts Supplier',
        ];
    }

    /** @return array<string, string> */
    public static function vendorStatuses(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'prospect' => 'Prospect',
            'suspended' => 'Suspended',
            'blacklisted' => 'Blacklisted',
            'closed' => 'Closed',
        ];
    }

    /** @return array<string, string> */
    public static function blacklistReasons(): array
    {
        return [
            'poor_quality' => 'Poor Quality',
            'late_delivery' => 'Late Delivery',
            'high_price' => 'High Price',
            'fraud' => 'Fraud',
            'fake_parts' => 'Fake Parts',
            'warranty_rejection' => 'Warranty Rejection',
            'poor_support' => 'Poor Support',
            'agreement_violation' => 'Agreement Violation',
        ];
    }

    /** @return array<string, string> */
    public static function paymentTermsOptions(): array
    {
        return [
            'advance' => 'Advance',
            'cash' => 'Cash',
            'immediate' => 'Immediate',
            '7_days' => '7 Days',
            '15_days' => '15 Days',
            '30_days' => '30 Days',
            '60_days' => '60 Days',
        ];
    }

    /** @return array<string, string> */
    public static function deliveryMethods(): array
    {
        return [
            'self_pickup' => 'Self Pickup',
            'vendor_delivery' => 'Vendor Delivery',
            'courier' => 'Courier',
            'transport' => 'Transport / Logistics',
        ];
    }

    /** @return array<int, string> */
    public static function ratings(): array
    {
        return [1 => '1 Star', 2 => '2 Star', 3 => '3 Star', 4 => '4 Star', 5 => '5 Star'];
    }

    public function vendorTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            VendorTypeMaster::class,
            'vendor_vendor_type',
            'vendor_id',
            'vendor_type_id',
        );
    }

    public function spareBrands(): BelongsToMany
    {
        return $this->belongsToMany(
            SpareBrandMaster::class,
            'spare_brand_vendor',
            'vendor_id',
            'spare_brand_id',
        );
    }

    public function serviceSpecialists(): BelongsToMany
    {
        return $this->belongsToMany(
            ServiceSpecialistMaster::class,
            'vendor_service_specialist',
            'vendor_id',
            'service_specialist_id',
        )->withTimestamps();
    }

    public function gstType(): BelongsTo
    {
        return $this->belongsTo(GstTypeMaster::class, 'gst_type_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(RegionMaster::class, 'region_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(BankMaster::class, 'bank_id');
    }

    public function terms(): HasMany
    {
        return $this->hasMany(VendorTerm::class, 'vendor_id')->orderBy('sort_order')->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VendorAttachment::class, 'vendor_id')->orderBy('sequence_no');
    }

    protected static function newFactory(): VendorMasterFactory
    {
        return VendorMasterFactory::new();
    }

    /**
     * Wipe KYC files from storage when the vendor record is deleted, so
     * orphaned blobs don't accumulate on S3 / disk.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $vendor) {
            foreach (['aadhar_file_path', 'pan_file_path'] as $col) {
                if ($vendor->{$col}) {
                    Storage::delete($vendor->{$col});
                }
            }

            foreach ($vendor->attachments as $attachment) {
                if ($attachment->path) {
                    Storage::disk('public')->delete($attachment->path);
                }
            }
        });
    }

    /** Inventory groups this vendor supplies — drives sourcing lookups. */
    public function inventoryGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            InventoryGroupMaster::class,
            'inventory_group_vendor',
            'vendor_id',
            'inventory_group_id',
        );
    }
}
