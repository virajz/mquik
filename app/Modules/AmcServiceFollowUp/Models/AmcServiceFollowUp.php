<?php

namespace App\Modules\AmcServiceFollowUp\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\AmcServiceFollowUp\Database\Factories\AmcServiceFollowUpFactory;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\VehicleAmc\Models\VehicleAmc;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AmcServiceFollowUp extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_INFORMED = 'informed';

    public const STATUS_QUOTATION_SENT = 'quotation_sent';

    public const STATUS_APPOINTMENT_BOOKED = 'appointment_booked';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_LOST = 'lost_opportunity';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'amc_service_follow_ups';

    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date',
        'odometer' => 'integer',
        'due_generated_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'attempt_at' => 'datetime',
        'response_at' => 'datetime',
        'appointment_at' => 'datetime',
        'arrival_at' => 'datetime',
        'job_card_open_at' => 'datetime',
    ];

    protected static array $searchableFields = ['follow_up_no', 'vehicle_history_reference', 'notes'];

    protected static function newFactory(): AmcServiceFollowUpFactory
    {
        return AmcServiceFollowUpFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->follow_up_no === null) {
                $row->forceFill(['follow_up_no' => 'ASF-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** Statuses still open (not converted / lost / cancelled). @return list<string> */
    public static function openStatuses(): array
    {
        return [
            self::STATUS_PENDING, self::STATUS_INFORMED, self::STATUS_QUOTATION_SENT,
            self::STATUS_APPOINTMENT_BOOKED, self::STATUS_OVERDUE,
        ];
    }

    /** @return array<string, string> */
    public static function followUpTypes(): array
    {
        return ['service_due' => 'Service Due', 'amc_renewal' => 'AMC Renewal'];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_INFORMED => 'Informed',
            self::STATUS_QUOTATION_SENT => 'Quotation Sent',
            self::STATUS_APPOINTMENT_BOOKED => 'Appointment Booked',
            self::STATUS_CONVERTED => 'Converted',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_LOST => 'Lost Opportunity',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
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
            'book_appointment' => 'Book Appointment',
            'vehicle_departed' => 'Vehicle Already Departed',
            'not_interested' => 'Not Interested',
            'vehicle_sold' => 'Vehicle Sold',
            'call_not_connected' => 'Call Not Connected',
            'call_not_received' => 'Call Not Received',
            'call_rejected' => 'Call Rejected',
            'wrong_number' => 'Wrong Number',
            'workshop_changed' => 'Workshop Changed',
        ];
    }

    /** @return array<string, string> */
    public static function satisfactions(): array
    {
        return ['satisfied' => 'Satisfied', 'dissatisfied' => 'Dissatisfied'];
    }

    /** @return array<string, string> */
    public static function lostReasons(): array
    {
        return [
            'price_concern' => 'Price Concern',
            'service_experience' => 'Service Experience',
            'staff_behaviour' => 'Staff Behaviour',
            'delay_delivery' => 'Delay in Delivery',
        ];
    }

    /** @return array<string, string> */
    public static function missedServiceReasons(): array
    {
        return [
            'customer_busy' => 'Customer Busy',
            'vehicle_not_available' => 'Vehicle Not Available',
            'out_of_station' => 'Customer Out of Station',
            'no_response' => 'No Response',
            'vehicle_sold' => 'Vehicle Sold',
        ];
    }

    /** @return array<string, string> */
    public static function escalations(): array
    {
        return ['advisor' => 'Escalated to Advisor', 'gm_owner' => 'Escalated to GM / Owner'];
    }

    /** @return array<string, string> */
    public static function escalationReasons(): array
    {
        return [
            'complaint_raised' => 'Complaint Raised',
            'vip_customer' => 'VIP Customer',
            'high_value_amc' => 'High Value AMC',
            'no_response' => 'No Customer Response',
            'missed_multiple' => 'Missed Multiple Follow-Ups',
        ];
    }

    /** @return array<string, string> */
    public static function retentions(): array
    {
        return ['active' => 'Active', 'lost' => 'Lost', 'recovered' => 'Recovered'];
    }

    /** @return array<string, string> */
    public static function intervalMethods(): array
    {
        return ['kilometer' => 'Kilometer Based', 'date' => 'Date Based'];
    }

    /** @return array<string, string> */
    public static function reminderFrequencies(): array
    {
        return [
            'before_7_days' => 'Before 7 Days',
            'today' => 'Today',
            'after_3_days' => 'After 3 Days',
            'after_7_days' => 'After 7 Days',
        ];
    }

    public function amc(): BelongsTo
    {
        return $this->belongsTo(VehicleAmc::class, 'vehicle_amc_id');
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
        return $this->hasMany(AmcServiceFollowUpAttachment::class, 'amc_service_follow_up_id')->orderBy('sequence_no');
    }
}
