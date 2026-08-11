<?php

namespace App\Modules\JobCard\Concerns;

use App\Modules\JobCard\Models\JobCard;
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
            ->orderByDesc('opened_at')
            ->limit(50)
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
            ->limit(100)
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

        return collect($seen)->sortByDesc('last_done_at')->values();
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
