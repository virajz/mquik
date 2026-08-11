<?php

namespace App\Modules\JobHistory\Livewire;

use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The whole life of one vehicle, in order.
 *
 * Where `Show` answers "what happened on this job card", this answers "what has
 * ever happened to this vehicle" — appointments and gate arrivals that precede a
 * card, every card's internal events, and the departures that close them out.
 */
#[Layout('layouts.app')]
#[Title('Vehicle Timeline')]
class VehicleTimeline extends Component
{
    public CustomerVehicleMaster $vehicle;

    /** Narrow to a single visit; empty shows the vehicle's whole life. */
    #[Url(as: 'card')]
    public ?int $jobCardFilter = null;

    public function mount(CustomerVehicleMaster $customerVehicle): void
    {
        $this->vehicle = $customerVehicle->load(['customer:id,first_name,last_name', 'variant:id,name']);
    }

    public function clearFilter(): void
    {
        $this->jobCardFilter = null;
    }

    /**
     * Events oldest-first, so the page reads as a story rather than a log tail.
     *
     * @return Collection<int, JobCardHistoryEvent>
     */
    public function events(): Collection
    {
        return JobCardHistoryEvent::query()
            ->where('customer_vehicle_id', $this->vehicle->id)
            ->when($this->jobCardFilter, fn ($q) => $q->where('job_card_id', $this->jobCardFilter))
            ->with(['actor:id,name', 'jobCard:id,job_card_no'])
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Distinct job cards on this vehicle's timeline, for the visit filter.
     *
     * @return Collection<int, JobCardHistoryEvent>
     */
    public function visits(): Collection
    {
        return JobCardHistoryEvent::query()
            ->where('customer_vehicle_id', $this->vehicle->id)
            ->whereNotNull('job_card_id')
            ->with('jobCard:id,job_card_no')
            ->get()
            ->unique('job_card_id')
            ->filter(fn ($e) => $e->jobCard !== null)
            ->sortByDesc('job_card_id')
            ->values();
    }

    public function render()
    {
        $events = $this->events();

        // Gap since the previous event — surfaces the delays the workshop cares
        // about (parts wait, document wait) without a separate calculation.
        $withGaps = $events->values()->map(function (JobCardHistoryEvent $e, int $i) use ($events) {
            $prev = $i > 0 ? $events->values()[$i - 1] : null;
            $gap = $prev && $prev->occurred_at && $e->occurred_at
                ? $prev->occurred_at->diffInMinutes($e->occurred_at)
                : null;

            return ['event' => $e, 'gap_minutes' => $gap !== null ? (int) $gap : null];
        });

        return view('job-history::vehicle-timeline', [
            'rows' => $withGaps,
            'visits' => $this->visits(),
            'firstAt' => $events->first()?->occurred_at,
            'lastAt' => $events->last()?->occurred_at,
        ]);
    }
}
