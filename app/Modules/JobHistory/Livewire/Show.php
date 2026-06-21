<?php

namespace App\Modules\JobHistory\Livewire;

use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\JobStageMaster\Models\JobStageMaster;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Job History')]
class Show extends Component
{
    public JobCard $jobCard;

    public function mount(JobCard $jobCard): void
    {
        $this->jobCard = $jobCard->load([
            'customer:id,first_name,last_name',
            'customerVehicle:id,registration_no',
            'currentStage:id,name,track,sort_order',
        ]);
    }

    public function render()
    {
        $events = JobCardHistoryEvent::query()
            ->where('job_card_id', $this->jobCard->id)
            ->with('actor:id,name,email')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get();

        // Build the stage tree for the card's track, with the entry timestamp of each stage.
        $track = $this->jobCard->currentStage?->track ?? JobStageMaster::TRACK_REGULAR;
        $stages = JobStageMaster::query()
            ->where('track', $track)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'sort_order']);

        // Earliest "stage_changed" event per target stage → when that stage was entered.
        $stageEnteredAt = $events
            ->where('event_type', JobCardHistoryEvent::TYPE_STAGE_CHANGED)
            ->sortBy('occurred_at')
            ->reduce(function (array $carry, $e) {
                $to = $e->payload['to'] ?? null;
                if ($to !== null && ! isset($carry[$to])) {
                    $carry[$to] = $e->occurred_at;
                }

                return $carry;
            }, []);

        return view('job-history::show', [
            'events' => $events,
            'types' => JobCardHistoryEvent::types(),
            'stages' => $stages,
            'currentStageId' => $this->jobCard->current_stage_id,
            'currentStageOrder' => $this->jobCard->currentStage?->sort_order ?? 0,
            'stageEnteredAt' => $stageEnteredAt,
        ]);
    }
}
