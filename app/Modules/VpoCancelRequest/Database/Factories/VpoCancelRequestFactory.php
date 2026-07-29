<?php

namespace App\Modules\VpoCancelRequest\Database\Factories;

use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VpoCancelRequest\Models\VpoCancelRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VpoCancelRequest>
 */
class VpoCancelRequestFactory extends Factory
{
    protected $model = VpoCancelRequest::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
            'cancellation_request_type' => 'full',
            'cancellation_reason' => 'wrong_part',
            'status' => VpoCancelRequest::STATUS_REQUESTED,
            'advance_payment_status' => 'no_advance',
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => VpoCancelRequest::STATUS_FULLY_ACCEPTED]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => VpoCancelRequest::STATUS_REJECTED, 'rejection_reason' => 'already_dispatched']);
    }

    public function refundPending(): static
    {
        return $this->state(fn () => ['advance_payment_status' => 'refund_requested']);
    }
}
