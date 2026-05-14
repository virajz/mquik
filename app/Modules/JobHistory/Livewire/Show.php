<?php

namespace App\Modules\JobHistory\Livewire;

use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
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

        return view('job-history::show', [
            'events' => $events,
            'types' => JobCardHistoryEvent::types(),
        ]);
    }
}
