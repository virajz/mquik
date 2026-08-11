<?php

namespace App\Modules\DocumentDelivery\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DocumentDelivery\Database\Factories\DocumentDeliveryFactory;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\MissingDocumentReasonMaster\Models\MissingDocumentReasonMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentDelivery extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_RE_SENT = 're_sent';

    protected $table = 'document_deliveries';

    protected $guarded = [];

    protected $casts = [
        'delivered_at' => 'datetime',
        'reminder_custom_days' => 'integer',
    ];

    protected static array $searchableFields = ['delivery_no', 'notes', 'jobCard.job_card_no'];

    protected static function newFactory(): DocumentDeliveryFactory
    {
        return DocumentDeliveryFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->delivery_no === null) {
                $row->forceFill([
                    'delivery_no' => 'DD-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_IN_TRANSIT => 'In Transit',
            self::STATUS_RETURNED => 'Returned',
            self::STATUS_RE_SENT => 'Re-sent',
        ];
    }

    /** @return array<string, string> */
    public static function recipientTypes(): array
    {
        return [
            'owner_self' => 'Owner Self',
            'on_behalf' => 'On Behalf of Owner',
        ];
    }

    /** @return array<string, string> */
    public static function deliveryModes(): array
    {
        return [
            'hand_to_hand' => 'Hand to Hand',
            'porter' => 'Porter',
            'courier' => 'Courier',
        ];
    }

    /** @return array<string, string> */
    public static function acknowledgementTypes(): array
    {
        return [
            'physical_sign' => 'Physical Signature',
            'digital_sign' => 'Digital Signature',
            'otp' => 'OTP Verification',
            'email' => 'Email Confirmation',
        ];
    }

    /** @return array<string, string> */
    public static function failureReasons(): array
    {
        return [
            'wrong_address' => 'Wrong Address',
            'door_locked' => 'Door Locked',
            'recipient_unavailable' => 'Recipient Unavailable',
        ];
    }

    /** @return array<string, string> */
    public static function reminderFrequencies(): array
    {
        return [
            'daily' => 'Daily',
            'every_2_days' => 'Every 2 Days',
            'custom' => 'Custom',
        ];
    }

    /** The standard delivery checklist offered as quick-add. @return list<string> */
    public static function standardDocuments(): array
    {
        return ['RC BOOK', 'INSURANCE POLICY', 'DRIVING LICENSE', 'AADHAR', 'PAN', 'PUC'];
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyMaster::class, 'insurance_company_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_employee_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'driver_employee_id');
    }

    public function courierCompany(): BelongsTo
    {
        return $this->belongsTo(CourierCompanyMaster::class, 'courier_company_id');
    }

    public function missingDocumentReason(): BelongsTo
    {
        return $this->belongsTo(MissingDocumentReasonMaster::class, 'missing_document_reason_id');
    }

    public function followUpMode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentDeliveryItem::class, 'document_delivery_id')->orderBy('sequence_no');
    }
}
