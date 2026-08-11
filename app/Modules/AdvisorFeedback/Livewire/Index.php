<?php

namespace App\Modules\AdvisorFeedback\Livewire;

use App\Modules\AdvisorFeedback\Models\AdvisorFeedback;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Advisor Feedback')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'feedback_no', 'status', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if (! in_array($column, $this->sortable, true)) {
            return;
        }
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function delete(int $id): void
    {
        $this->authorize('advisor_feedback.delete');
        AdvisorFeedback::findOrFail($id)->delete();
        Flux::toast(text: 'Advisor feedback #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    /**
     * @return array{pending:int, submitted:int, avg_rating:float}
     */
    protected function kpis(): array
    {
        $submitted = AdvisorFeedback::query()->where('status', AdvisorFeedback::STATUS_SUBMITTED)->get();
        $avg = $submitted->map->averageRating()->filter(fn ($v) => $v !== null);

        return [
            'pending' => AdvisorFeedback::query()->where('status', AdvisorFeedback::STATUS_PENDING)->count(),
            'submitted' => $submitted->count(),
            'avg_rating' => $avg->isNotEmpty() ? round($avg->avg(), 2) : 0.0,
        ];
    }

    /**
     * @return Builder<AdvisorFeedback>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return AdvisorFeedback::query()
            ->with(['customer:id,first_name,last_name', 'advisor:id,name'])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter));
    }

    /** Stream the Advisor Feedback CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('advisor_feedback.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'advisor-feedback-'.Carbon::now()->format('Ymd-His').'.csv';
        $statuses = AdvisorFeedback::statuses();

        return response()->streamDownload(function () use ($rows, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Feedback No', 'Advisor', 'Customer', 'Cooperative', 'Approvals', 'Payment', 'Professional', 'Prefer Again', 'Avg', 'Status', 'Submitted']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->feedback_no,
                    $r->advisor?->name,
                    trim(($r->customer?->first_name ?? '').' '.($r->customer?->last_name ?? '')),
                    $r->cooperative_rating,
                    $r->timely_approvals_rating,
                    $r->payment_committed_rating,
                    $r->professional_rating,
                    $r->prefer_again_rating,
                    $r->averageRating(),
                    $statuses[$r->status] ?? $r->status,
                    $r->submitted_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('advisor-feedback::index', [
            'rows' => $rows,
            'statuses' => AdvisorFeedback::statuses(),
            'kpis' => $this->kpis(),
        ]);
    }
}
