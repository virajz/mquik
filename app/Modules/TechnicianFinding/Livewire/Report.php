<?php

namespace App\Modules\TechnicianFinding\Livewire;

use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\JobHistory\Support\JobCardHistoryRecorder;
use App\Modules\NotificationCenter\Models\AppNotification;
use App\Modules\NotificationCenter\Support\Notifier;
use App\Modules\TechnicianFinding\Models\TechnicianFinding;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * What the technicians found, gathered into one thing the advisor can act on.
 *
 * The workshop's ask: after inspection, the advisor needs a single statement of
 * "here is what this vehicle needs and what it will cost" to take to the
 * customer — rather than reading findings one at a time.
 */
#[Layout('layouts.app')]
#[Title('Findings Report')]
class Report extends Component
{
    public JobCard $jobCard;

    /** Only findings still awaiting a decision, by default. */
    public bool $openOnly = true;

    public function mount(JobCard $jobCard): void
    {
        $this->jobCard = $jobCard->load([
            'customer:id,first_name,last_name,phone',
            'customerVehicle:id,registration_no',
            'advisor:id,name',
        ]);
    }

    /** @return Collection<int, TechnicianFinding> */
    #[Computed]
    public function findings(): Collection
    {
        return TechnicianFinding::query()
            ->where('job_card_id', $this->jobCard->id)
            ->when($this->openOnly, fn ($q) => $q->where('status', TechnicianFinding::STATUS_RECOMMENDED))
            ->with(['reportedBy:id,name', 'spare:id,name', 'labour:id,name'])
            ->orderBy('id')
            ->get();
    }

    /** Total of the estimated amounts on the findings shown. */
    #[Computed]
    public function estimatedTotal(): float
    {
        return (float) $this->findings->sum(fn (TechnicianFinding $f) => (float) $f->estimated_amount);
    }

    /** A plain-text version the advisor can read to the customer or paste. */
    #[Computed]
    public function summaryText(): string
    {
        $lines = [];
        $lines[] = 'Recommended work — '.$this->jobCard->job_card_no;

        if ($this->jobCard->customerVehicle?->registration_no) {
            $lines[] = 'Vehicle: '.$this->jobCard->customerVehicle->registration_no;
        }

        $lines[] = '';

        foreach ($this->findings as $i => $f) {
            $amount = $f->estimated_amount ? ' — Rs '.number_format((float) $f->estimated_amount, 2) : '';
            $lines[] = ($i + 1).'. '.$f->description.$amount;

            if ($f->recommendation) {
                $lines[] = '   '.$f->recommendation;
            }
        }

        if ($this->findings->isEmpty()) {
            $lines[] = 'No open findings.';
        } else {
            $lines[] = '';
            $lines[] = 'Estimated total: Rs '.number_format($this->estimatedTotal, 2);
        }

        return implode("\n", $lines);
    }

    /**
     * Push the report at the advisor. This is the "report sent to advisor" step —
     * it lands in their notifications and on the job card's timeline, so there is
     * a record that the workshop told them.
     */
    public function sendToAdvisor(): void
    {
        $this->authorize('technician_finding.view');

        $advisorId = $this->jobCard->assigned_advisor_id;

        if (! $advisorId) {
            Flux::toast(text: 'This job card has no advisor assigned.', variant: 'warning');

            return;
        }

        if ($this->findings->isEmpty()) {
            Flux::toast(text: 'There are no open findings to report.', variant: 'warning');

            return;
        }

        Notifier::toEmployee($advisorId, AppNotification::TYPE_FINDINGS_REPORT, [
            'title' => 'Findings report — '.$this->jobCard->job_card_no,
            'body' => $this->findings->count().' item(s) recommended, estimated Rs '
                .number_format($this->estimatedTotal, 2).'.',
            'url' => route('technician-finding.report', $this->jobCard->id),
            'subject' => $this->jobCard,
        ]);

        JobCardHistoryRecorder::record(
            $this->jobCard->id,
            JobCardHistoryEvent::TYPE_FINDING_RECORDED,
            'Findings report sent to advisor ('.$this->findings->count().' items)',
            ['count' => $this->findings->count(), 'estimated_total' => $this->estimatedTotal],
        );

        Flux::toast(text: 'Report sent to the advisor.', variant: 'success');
    }

    public function render()
    {
        return view('technician-finding::report');
    }
}
