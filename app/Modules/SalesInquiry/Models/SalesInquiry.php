<?php

namespace App\Modules\SalesInquiry\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\SalesInquiry\Database\Factories\SalesInquiryFactory;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInquiry extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_QUOTATION_SENT = 'quotation_sent';

    public const STATUS_APPOINTMENT_BOOKED = 'appointment_booked';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_LOST = 'lost_opportunity';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'sales_inquiries';

    protected $guarded = [];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'inquiry_at' => 'datetime',
        'assigned_at' => 'datetime',
        'quotation_at' => 'datetime',
        'follow_up_at' => 'datetime',
        'converted_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static array $searchableFields = ['inquiry_no', 'inquiry_details', 'notes'];

    protected static function newFactory(): SalesInquiryFactory
    {
        return SalesInquiryFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->inquiry_no === null) {
                $row->forceFill(['inquiry_no' => 'INQ-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** Statuses still open (not converted / lost / cancelled). @return list<string> */
    public static function openStatuses(): array
    {
        return [self::STATUS_PENDING, self::STATUS_ASSIGNED, self::STATUS_QUOTATION_SENT, self::STATUS_APPOINTMENT_BOOKED];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_QUOTATION_SENT => 'Quotation Sent',
            self::STATUS_APPOINTMENT_BOOKED => 'Appointment Booked',
            self::STATUS_CONVERTED => 'Converted',
            self::STATUS_LOST => 'Lost Opportunity',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function inquiryTypes(): array
    {
        return [
            'periodic_maintenance' => 'Periodic Maintenance Service',
            'general_service' => 'General Service',
            'amc' => 'AMC',
            'breakdown_repair' => 'Breakdown Repair',
            'denting_painting' => 'Denting Painting',
            'ac_repair' => 'AC Repair',
            'consumables' => 'Consumables',
            'accessories' => 'Accessories',
            'tyre' => 'Tyre',
            'battery' => 'Battery',
            'lubricant' => 'Lubricant',
            'insurance_renewal' => 'Ins. Policy Renewal',
            'other' => 'Other',
        ];
    }

    /** @return array<string, string> */
    public static function inquirySources(): array
    {
        return [
            'whatsapp' => 'WhatsApp',
            'sms' => 'SMS',
            'email' => 'Email',
            'website' => 'Website',
            'google_search' => 'Google Search',
            'social_media' => 'Social Media',
            'phone_call' => 'Phone Call',
            'mobile_app' => 'Mobile App',
            'walk_in' => 'Walk-In',
        ];
    }

    /** @return array<string, string> */
    public static function priorities(): array
    {
        return ['normal' => 'Normal', 'medium' => 'Medium', 'high' => 'High'];
    }

    /** @return array<string, string> */
    public static function followUpAttempts(): array
    {
        return ['first' => '1st', 'second' => '2nd', 'third' => '3rd', 'final' => 'Final'];
    }

    /** @return array<string, string> */
    public static function escalations(): array
    {
        return ['senior_executive' => 'Escalated to Senior Executive', 'gm_owner' => 'Escalated to GM / Owner'];
    }

    /** @return array<string, string> */
    public static function escalationReasons(): array
    {
        return ['complaint_raised' => 'Complaint Raised', 'vip_customer' => 'VIP Customer', 'high_value' => 'High Value'];
    }

    /** @return array<string, string> */
    public static function lostReasons(): array
    {
        return [
            'price_too_high' => 'Price Too High',
            'parts_not_available' => 'Parts Not Available',
            'response_delay' => 'Response Delay',
            'no_response' => 'No Response',
            'vehicle_sold' => 'Vehicle Sold',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'assigned_by_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'assigned_to_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SalesInquiryAttachment::class, 'sales_inquiry_id')->orderBy('sequence_no');
    }
}
