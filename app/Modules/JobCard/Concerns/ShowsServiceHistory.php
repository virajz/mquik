<?php

namespace App\Modules\JobCard\Concerns;

use App\Modules\JobCard\Models\JobCard;
use App\Modules\RegularSalesInvoice\Models\RegularSalesInvoice;
use App\Modules\ServiceIntervalMaster\Models\ServiceIntervalMaster;
use App\Support\AppSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * The advisor's view of what this vehicle has had done.
 *
 * Two questions get answered: "when did we last do X?" (so the advisor can
 * recommend what's due) and "which visits included X?" (so they can point at
 * the evidence). Services come from the complaints raised and the repairs
 * requested on each past card.
 */
trait ShowsServiceHistory
{
    /** Free-text filter over the vehicle's past work — e.g. "oil change". */
    public string $historySearch = '';

    /** Past cards for this vehicle, newest first, optionally filtered by service. */
    #[Computed]
    public function vehicleJobCards()
    {
        if (! $this->customer_vehicle_id) {
            return collect();
        }

        $term = trim($this->historySearch);

        return JobCard::query()
            ->where('customer_vehicle_id', $this->customer_vehicle_id)
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->when($term !== '', fn ($q) => $q->search($term))
            ->with([
                'advisor:id,name',
                'currentStage:id,name',
                'workshopDepartment:id,name',
                'complaints:id,job_card_id,description',
                'requestedRepairs:id,name',
            ])
            // A customer remembers the bill, not the job card: the invoice date
            // and number are what they quote back, so history leads with those.
            ->addSelect([
                'billed_at' => RegularSalesInvoice::select('invoiced_at')
                    ->whereColumn('regular_sales_invoices.job_card_id', 'job_cards.id')
                    ->latest('invoiced_at')->limit(1),
                'invoice_no' => RegularSalesInvoice::select('invoice_no')
                    ->whereColumn('regular_sales_invoices.job_card_id', 'job_cards.id')
                    ->latest('invoiced_at')->limit(1),
            ])
            ->orderByDesc('opened_at')
            ->limit((int) AppSettings::int('service_history.visits_listed', 50))
            ->get(['id', 'job_card_no', 'status', 'current_stage_id', 'assigned_advisor_id', 'workshop_department_id', 'opened_at', 'promised_at', 'closed_at', 'km_at_service']);
    }

    /**
     * Every service this vehicle has ever had, with when it was last done.
     *
     * Unfiltered by the search box on purpose — this is the recommendation list,
     * and it should stay stable while the advisor filters the visits below it.
     *
     * @return Collection<int, array{service: string, last_done_at: ?Carbon, last_km: ?int, job_card_no: ?string, times: int}>
     */
    #[Computed]
    public function serviceHistory(): Collection
    {
        if (! $this->customer_vehicle_id) {
            return collect();
        }

        $cards = JobCard::query()
            ->where('customer_vehicle_id', $this->customer_vehicle_id)
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->with(['complaints:id,job_card_id,description', 'requestedRepairs:id,name'])
            ->orderByDesc('opened_at')
            ->limit((int) AppSettings::int('service_history.visits_scanned', 100))
            ->get(['id', 'job_card_no', 'opened_at', 'km_at_service']);

        $seen = [];

        foreach ($cards as $card) {
            foreach ($this->servicesOn($card) as $service) {
                $key = mb_strtoupper($service);

                if (! isset($seen[$key])) {
                    // Cards are newest-first, so the first sighting is the latest.
                    $seen[$key] = [
                        'service' => $key,
                        'last_done_at' => $card->opened_at,
                        'last_km' => $card->km_at_service !== null ? (int) $card->km_at_service : null,
                        'job_card_no' => $card->job_card_no,
                        'times' => 0,
                    ];
                }

                $seen[$key]['times']++;
            }
        }

        $rows = collect($seen)->map(fn (array $row) => $row + $this->dueState($row));

        return $this->applyServiceSort($rows)->values();
    }

    /**
     * @param  Collection<int|string, array<string, mixed>>  $rows
     * @return Collection<int|string, array<string, mixed>>
     */
    protected function applyServiceSort(Collection $rows): Collection
    {
        $recent = fn ($a, $b) => ($b['last_done_at']?->timestamp ?? 0) <=> ($a['last_done_at']?->timestamp ?? 0);
        $due = fn ($a, $b) => ($b['is_due'] ? 1 : 0) <=> ($a['is_due'] ? 1 : 0);

        return match (AppSettings::get('service_history.sort_mode', 'due_first')) {
            // Worst offender first — a service 3 years past its interval outranks
            // one that slipped last week.
            'most_overdue' => $rows->sortBy([
                fn ($a, $b) => $b['overdue_score'] <=> $a['overdue_score'],
                $recent,
            ]),
            'recent' => $rows->sortBy([$recent]),
            'frequent' => $rows->sortBy([
                fn ($a, $b) => $b['times'] <=> $a['times'],
                $recent,
            ]),
            'alphabetical' => $rows->sortBy('service'),
            // Default: anything due floats up, the rest stay newest-first.
            default => $rows->sortBy([$due, $recent]),
        };
    }

    /**
     * Is this service due, and why?
     *
     * Time and distance are both checked — whichever comes first wins, which is
     * how a service schedule actually reads. A service with no configured
     * interval falls back to the config age, or is never flagged if that is null.
     *
     * @param  array<string, mixed>  $row
     * @return array{is_due: bool, due_reason: ?string, interval: ?string}
     */
    protected function dueState(array $row): array
    {
        $none = ['is_due' => false, 'due_reason' => null, 'interval' => null, 'overdue_score' => 0.0];

        if (! $row['last_done_at']) {
            return $none;
        }

        $months = (int) $row['last_done_at']->diffInMonths(now());
        $interval = $this->serviceIntervals->get(mb_strtoupper($row['service']));

        // No interval on file — fall back to the blanket age rule, if enabled.
        if (! $interval) {
            $fallback = AppSettings::int('service_history.default_overdue_months');

            return $fallback && $months >= (int) $fallback
                ? ['is_due' => true, 'due_reason' => $this->agoLabel($months), 'interval' => null, 'overdue_score' => $months / max(1, (int) $fallback)]
                : $none;
        }

        if (! $interval->hasBound()) {
            return ['is_due' => false, 'due_reason' => null, 'interval' => $interval->label(), 'overdue_score' => 0.0];
        }

        $reasons = [];
        // Ratio, not raw numbers: months and kilometres are not comparable, but
        // "twice its interval" is the same idea in either unit.
        $score = 0.0;

        if ($interval->interval_months !== null && $months >= $interval->interval_months) {
            $score = max($score, $months / max(1, $interval->interval_months));
            $reasons[] = $this->agoLabel($months);
        }

        // Distance since the reading taken at that visit.
        $currentKm = (int) ($this->km_at_service ?: 0);
        if ($interval->interval_km !== null && $row['last_km'] && $currentKm > $row['last_km']) {
            $covered = $currentKm - (int) $row['last_km'];
            if ($covered >= $interval->interval_km) {
                $reasons[] = number_format($covered).' km since';
                $score = max($score, $covered / max(1, $interval->interval_km));
            }
        }

        return [
            'is_due' => $reasons !== [],
            'due_reason' => $reasons === [] ? null : implode(' · ', $reasons),
            'interval' => $interval->label(),
            'overdue_score' => $score,
        ];
    }

    /** "3y ago" / "8m ago". */
    protected function agoLabel(int $months): string
    {
        return $months >= 12 ? intdiv($months, 12).'y ago' : $months.'m ago';
    }

    /**
     * Configured intervals, keyed by uppercase service name.
     *
     * @return Collection<string, ServiceIntervalMaster>
     */
    #[Computed]
    public function serviceIntervals(): Collection
    {
        return ServiceIntervalMaster::query()
            ->where('is_active', true)
            ->get()
            ->keyBy(fn (ServiceIntervalMaster $i) => mb_strtoupper($i->name));
    }

    /**
     * Service labels on one card — complaint text plus requested repairs.
     *
     * @return list<string>
     */
    protected function servicesOn(JobCard $card): array
    {
        $fromComplaints = $card->complaints
            ->pluck('description')
            ->filter()
            ->map(fn (string $d) => trim($d));

        $fromRepairs = $card->requestedRepairs->pluck('name')->filter();

        return $fromComplaints->merge($fromRepairs)
            ->filter(fn ($s) => $s !== '')
            ->unique()
            ->values()
            ->all();
    }
}
