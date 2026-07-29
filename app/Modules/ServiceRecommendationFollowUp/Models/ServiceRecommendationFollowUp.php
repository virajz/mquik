<?php

namespace App\Modules\ServiceRecommendationFollowUp\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\ServiceRecommendationFollowUp\Database\Factories\ServiceRecommendationFollowUpFactory;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceRecommendationFollowUp extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_INFORMED = 'informed';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_QUOTATION_SENT = 'quotation_sent';

    public const STATUS_APPOINTMENT_BOOKED = 'appointment_booked';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_LOST = 'lost_opportunity';

    public const STATUS_CANCELLED = 'cancelled';

    public const CATEGORY_SAFETY = 'safety';

    protected $table = 'service_recommendation_follow_ups';

    protected $guarded = [];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'recommended_at' => 'datetime',
        'informed_at' => 'datetime',
        'follow_up_at' => 'datetime',
        'estimate_at' => 'datetime',
        'appointment_at' => 'datetime',
    ];

    protected static array $searchableFields = ['recommendation_no', 'recommended_service', 'notes'];

    protected static function newFactory(): ServiceRecommendationFollowUpFactory
    {
        return ServiceRecommendationFollowUpFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->recommendation_no === null) {
                $row->forceFill(['recommendation_no' => 'SRF-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** Statuses still open (not converted / lost / cancelled). @return list<string> */
    public static function openStatuses(): array
    {
        return [
            self::STATUS_PENDING, self::STATUS_ASSIGNED, self::STATUS_INFORMED, self::STATUS_ACCEPTED,
            self::STATUS_QUOTATION_SENT, self::STATUS_APPOINTMENT_BOOKED,
        ];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_INFORMED => 'Informed',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_QUOTATION_SENT => 'Quotation Sent',
            self::STATUS_APPOINTMENT_BOOKED => 'Appointment Booked',
            self::STATUS_CONVERTED => 'Converted',
            self::STATUS_LOST => 'Lost Opportunity',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function priorities(): array
    {
        return ['normal' => 'Normal', 'medium' => 'Medium', 'high' => 'High'];
    }

    /** @return array<string, string> */
    public static function recommendationTypes(): array
    {
        return [
            'engine' => 'Engine',
            'gearbox' => 'Gearbox',
            'clutch' => 'Clutch',
            'suspension' => 'Suspension',
            'steering' => 'Steering',
            'brake' => 'Brake',
            'electrical' => 'Electrical',
            'ac' => 'AC',
            'denting' => 'Denting',
            'painting' => 'Painting',
            'pms' => 'PMS',
        ];
    }

    /** @return array<string, string> */
    public static function recommendationReasons(): array
    {
        return [
            'leakage' => 'Leakage',
            'worn_out' => 'Worn Out',
            'damaged' => 'Damaged',
            'excessive_wear' => 'Excessive Wear',
            'noise' => 'Noise',
            'performance_issue' => 'Performance Issue',
            'preventive' => 'Preventive',
            'manufacturer' => 'Manufacturer Recommendation',
        ];
    }

    /** @return array<string, string> */
    public static function recommendationCategories(): array
    {
        return [
            self::CATEGORY_SAFETY => 'Safety',
            'preventive_maintenance' => 'Preventive Maintenance',
            'performance' => 'Performance',
            'comfort' => 'Comfort',
            'regulatory' => 'Regulatory Compliance',
        ];
    }

    /** @return array<string, string> */
    public static function reminderFrequencies(): array
    {
        return ['before_7_days' => 'Before 7 Days', 'today' => 'Today', 'after_1_week' => 'After 1 Week', 'after_2_week' => 'After 2 Weeks'];
    }

    /** @return array<string, string> */
    public static function followUpAttempts(): array
    {
        return ['first' => '1st', 'second' => '2nd', 'third' => '3rd', 'final' => 'Final'];
    }

    /** @return array<string, string> */
    public static function followUpModes(): array
    {
        return ['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'email' => 'Email', 'call' => 'Telephonic Call'];
    }

    /** @return array<string, string> */
    public static function customerResponses(): array
    {
        return [
            'callback_later' => 'Callback Later',
            'will_visit_later' => 'Will Visit Later',
            'quote_requested' => 'Quote Requested',
            'book_appointment' => 'Book Appointment',
            'vehicle_departed' => 'Vehicle Already Departed',
            'not_interested' => 'Not Interested',
            'vehicle_sold' => 'Vehicle Sold',
            'call_not_connected' => 'Call Not Connected',
            'call_not_received' => 'Call Not Received',
            'call_rejected' => 'Call Rejected',
            'no_response' => 'No Response',
            'wrong_number' => 'Wrong Number',
            'workshop_changed' => 'Workshop Changed',
            'serviced_elsewhere' => 'Serviced Elsewhere',
        ];
    }

    /** @return array<string, string> */
    public static function escalations(): array
    {
        return ['advisor' => 'Escalated to Advisor', 'floor_gm_owner' => 'Escalated to Floor In-charge / GM / Owner'];
    }

    /** @return array<string, string> */
    public static function escalationReasons(): array
    {
        return ['complaint_raised' => 'Complaint Raised', 'vip_customer' => 'VIP Customer', 'high_value' => 'High Value'];
    }

    /** @return array<string, string> */
    public static function satisfactions(): array
    {
        return ['satisfied' => 'Satisfied', 'dissatisfied' => 'Dissatisfied'];
    }

    /** @return array<string, string> */
    public static function retentions(): array
    {
        return ['active' => 'Active', 'lost' => 'Lost', 'recovered' => 'Recovered'];
    }

    /** @return array<string, string> */
    public static function lostReasons(): array
    {
        return [
            'financial_issue' => 'Financial Issue',
            'service_experience' => 'Service Experience',
            'staff_behaviour' => 'Staff Behaviour',
            'delay_delivery' => 'Delay in Delivery',
            'vehicle_sold' => 'Vehicle Sold',
            'serviced_elsewhere' => 'Serviced Elsewhere',
            'no_response' => 'No Response',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function followUpBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'follow_up_by_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ServiceRecommendationFollowUpAttachment::class, 'service_recommendation_follow_up_id')->orderBy('sequence_no');
    }
}
