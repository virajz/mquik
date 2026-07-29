<?php

namespace App\Modules\CustomerFeedback\Livewire;

use App\Modules\CustomerFeedback\Models\CustomerFeedback;
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
#[Title('Customer Feedback')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'cat')]
    public string $categoryFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'feedback_no', 'status', 'service_rating', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
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
        $this->authorize('customer_feedback.delete');
        CustomerFeedback::findOrFail($id)->delete();
        Flux::toast(text: 'Feedback #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'categoryFilter']);
        $this->resetPage();
    }

    /**
     * @return array{avg_rating:float, response_rate:float, negative:int, total:int}
     */
    protected function kpis(): array
    {
        $total = CustomerFeedback::query()->count();
        $submitted = CustomerFeedback::query()->whereNotNull('submitted_at')->count();

        return [
            'avg_rating' => round((float) CustomerFeedback::query()->whereNotNull('service_rating')->avg('service_rating'), 2),
            'response_rate' => $total > 0 ? round($submitted / $total * 100, 1) : 0.0,
            'negative' => CustomerFeedback::query()
                ->where(function ($q) {
                    $q->where('status', CustomerFeedback::STATUS_DISSATISFIED)
                        ->orWhere('service_rating', '<=', 2);
                })->count(),
            'total' => $total,
        ];
    }

    /**
     * @return Builder<CustomerFeedback>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return CustomerFeedback::query()
            ->with(['customer:id,first_name,last_name', 'advisor:id,name'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('feedback_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('invoice_reference', '%'.$search.'%', caseSensitive: false);
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->categoryFilter !== 'all', fn ($q) => $q->where('feedback_category', $this->categoryFilter));
    }

    /** Stream the Customer Feedback CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('customer_feedback.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'customer-feedback-'.Carbon::now()->format('Ymd-His').'.csv';
        $statuses = CustomerFeedback::statuses();
        $sources = CustomerFeedback::feedbackSources();

        return response()->streamDownload(function () use ($rows, $statuses, $sources) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Feedback No', 'Customer', 'Advisor', 'Source', 'Service Rating', 'Avg Rating', 'Recommend', 'Status', 'Submitted']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->feedback_no,
                    trim(($r->customer?->first_name ?? '').' '.($r->customer?->last_name ?? '')),
                    $r->advisor?->name,
                    $sources[$r->feedback_source] ?? '',
                    $r->service_rating,
                    $r->averageRating(),
                    $r->would_recommend === null ? '' : ($r->would_recommend ? 'Yes' : 'No'),
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

        // Advisor-wise average service rating.
        $byAdvisor = CustomerFeedback::query()
            ->join('employees', 'employees.id', '=', 'customer_feedbacks.advisor_id')
            ->whereNotNull('customer_feedbacks.service_rating')
            ->selectRaw('employees.name as advisor, round(avg(customer_feedbacks.service_rating), 2) as rating, count(*) as responses')
            ->groupBy('employees.name')
            ->orderByDesc('rating')
            ->limit(8)
            ->get();

        return view('customer-feedback::index', [
            'rows' => $rows,
            'statuses' => CustomerFeedback::statuses(),
            'categories' => CustomerFeedback::feedbackCategories(),
            'kpis' => $this->kpis(),
            'byAdvisor' => $byAdvisor,
        ]);
    }
}
