<?php

namespace App\Modules\VehicleModelMaster\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Works out when a vehicle is next due for service from its interval and its
 * recent usage.
 *
 * A service falls due on whichever comes first: the odometer crossing the km
 * interval, or the calendar crossing the month interval. The km side is
 * translated into a date using the vehicle's average daily distance, so both
 * limbs can be compared as dates and the earlier one wins.
 *
 * This is a pure calculator — it reads nothing and sends nothing. Callers feed
 * it whatever service history exists (last job card, odometer readings); the
 * reminder itself is the SMS integration's job, not this class's.
 */
class ServiceSchedule
{
    /**
     * @param  array{km:?int, months:?int}  $interval  the vehicle's effective interval
     * @return array{due_at:?CarbonInterface, due_by_km_at:?CarbonInterface, due_km:?int, by:?string}
     *                                                                                                `by` is 'km', 'time', or 'both' — which limb drives the date.
     */
    public static function nextDue(
        array $interval,
        CarbonInterface $lastServicedAt,
        ?int $lastServiceOdometer = null,
        ?float $avgKmPerDay = null,
    ): array {
        $last = CarbonImmutable::parse($lastServicedAt);

        $dueByTime = $interval['months']
            ? $last->addMonths($interval['months'])
            : null;

        // The km limb only yields a date when we know both the target odometer
        // and how fast the vehicle accrues distance.
        $dueKm = null;
        $dueByKm = null;
        if ($interval['km'] && $lastServiceOdometer !== null && $avgKmPerDay !== null && $avgKmPerDay > 0) {
            $dueKm = $lastServiceOdometer + $interval['km'];
            $daysToInterval = (int) ceil($interval['km'] / $avgKmPerDay);
            $dueByKm = $last->addDays($daysToInterval);
        }

        $dueAt = self::earliest($dueByTime, $dueByKm);

        $by = null;
        if ($dueAt !== null) {
            $onTime = $dueByTime !== null && $dueAt->equalTo($dueByTime);
            $onKm = $dueByKm !== null && $dueAt->equalTo($dueByKm);
            $by = match (true) {
                $onTime && $onKm => 'both',
                $onKm => 'km',
                default => 'time',
            };
        }

        return [
            'due_at' => $dueAt,
            'due_by_km_at' => $dueByKm,
            'due_km' => $dueKm,
            'by' => $by,
        ];
    }

    private static function earliest(?CarbonInterface $a, ?CarbonInterface $b): ?CarbonInterface
    {
        if ($a === null) {
            return $b;
        }
        if ($b === null) {
            return $a;
        }

        return $a->lessThanOrEqualTo($b) ? $a : $b;
    }
}
