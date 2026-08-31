<?php

namespace App\Modules\VehicleInspectionOrder\Concerns;

use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrderScope;

/**
 * Start / pause / complete semantics for a work-order task line.
 *
 * Shared by the advisor's order form and the technician bench so both screens
 * agree on the one rule that matters: a technician runs at most one task at a
 * time, and switching tasks folds the running segment into the previous line.
 */
trait ManagesScopeTimers
{
    /**
     * Begin (or resume) a task line for a technician, pausing whatever else
     * that technician had running.
     */
    protected function startScopeTimer(VehicleInspectionOrderScope $scope, int $technicianId): void
    {
        VehicleInspectionOrderScope::query()
            ->where('technician_id', $technicianId)
            ->where('work_status', VehicleInspectionOrderScope::STATUS_IN_PROGRESS)
            ->whereKeyNot($scope->id)
            ->get()
            ->each(fn (VehicleInspectionOrderScope $other) => $this->accumulateAndStop($other, VehicleInspectionOrderScope::STATUS_PAUSED));

        $scope->forceFill([
            'technician_id' => $technicianId,
            'work_status' => VehicleInspectionOrderScope::STATUS_IN_PROGRESS,
            'run_started_at' => now(),
            // Only the first start: resuming after a pause must not move the
            // time work began, or the TAT shrinks every time they take a break.
            'work_started_at' => $scope->work_started_at ?? now(),
            'completed_at' => null,
        ])->save();
    }

    /** Stop the line and stamp it completed. */
    protected function completeScopeTimer(VehicleInspectionOrderScope $scope): void
    {
        $this->accumulateAndStop($scope, VehicleInspectionOrderScope::STATUS_COMPLETED);
        $scope->forceFill(['completed_at' => now()])->save();
    }

    /** Fold the current running segment into the accumulated total and stop. */
    protected function accumulateAndStop(VehicleInspectionOrderScope $scope, string $status): void
    {
        $accrued = $scope->run_started_at
            ? max(0, now()->getTimestamp() - $scope->run_started_at->getTimestamp())
            : 0;

        $scope->forceFill([
            'duration_seconds' => (int) $scope->duration_seconds + $accrued,
            'run_started_at' => null,
            'work_status' => $status,
        ])->save();
    }
}
