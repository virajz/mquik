<?php

namespace App\Modules\StockMismatchApproval\Database\Factories;

use App\Modules\StockMismatchApproval\Models\StockMismatchApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMismatchApproval>
 */
class StockMismatchApprovalFactory extends Factory
{
    protected $model = StockMismatchApproval::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approval_status' => StockMismatchApproval::STATUS_REQUESTED,
            'variance_reason' => 'data_entry_error',
            'communication_mode' => 'whatsapp',
            'requested_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'approval_status' => StockMismatchApproval::STATUS_APPROVED,
            'management_response' => 'adjust',
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'approval_status' => StockMismatchApproval::STATUS_REJECTED,
            'rejected_at' => now(),
        ]);
    }

    public function underReview(): static
    {
        return $this->state(fn () => [
            'approval_status' => StockMismatchApproval::STATUS_UNDER_REVIEW,
        ]);
    }
}
