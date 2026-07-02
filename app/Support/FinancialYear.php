<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Indian financial-year helpers (April–March).
 *
 * Used to build the FY-aware invoice series, e.g. MQ/25-26/00001.
 */
class FinancialYear
{
    /**
     * Two-digit financial-year label for a given date, e.g. "25-26".
     * April–December belongs to {year}-{year+1}; January–March to {year-1}-{year}.
     */
    public static function label(CarbonInterface|string|null $date = null): string
    {
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date ?? now());

        $startYear = $date->month >= 4 ? $date->year : $date->year - 1;

        return sprintf('%02d-%02d', $startYear % 100, ($startYear + 1) % 100);
    }
}
