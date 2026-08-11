<?php

namespace App\Livewire;

use App\Support\RelatedRecords;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The "everything about this record" flyout.
 *
 * Dropped onto a customer, vehicle or job card form; lists every area of the app
 * holding related work, with counts, and links straight into that area filtered
 * to this record.
 */
class RecordPanel extends Component
{
    /** One of the RelatedRecords::SUBJECT_* values. */
    public string $subject = RelatedRecords::SUBJECT_CUSTOMER;

    public ?int $recordId = null;

    /** Shown as the flyout's subtitle — e.g. the customer name or job card no. */
    public ?string $recordLabel = null;

    /**
     * @return list<array{label: string, icon: string, count: int, url: string}>
     */
    #[Computed]
    public function areas(): array
    {
        if (! $this->recordId) {
            return [];
        }

        return RelatedRecords::for($this->subject, $this->recordId);
    }

    public function render()
    {
        return view('livewire.record-panel');
    }
}
