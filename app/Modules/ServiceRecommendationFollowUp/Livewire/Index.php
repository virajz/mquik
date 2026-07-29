<?php

namespace App\Modules\ServiceRecommendationFollowUp\Livewire;

use App\Modules\ServiceRecommendationFollowUp\Models\ServiceRecommendationFollowUp;
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
#[Title('Service Recommendation Follow-Ups')]
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

    protected array $sortable = ['id', 'recommendation_no', 'status', 'priority', 'created_at'];

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
        $this->authorize('service_recommendation_follow_up.delete');
        ServiceRecommendationFollowUp::findOrFail($id)->delete();
        Flux::toast(text: 'Recommendation #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'categoryFilter']);
        $this->resetPage();
    }

    /**
     * @return array{open:int, today:int, upcoming:int, safety:int, converted:int, lost:int, conversion_rate:float, revenue:float}
     */
    protected function kpis(): array
    {
        $today = Carbon::today();
        $open = ServiceRecommendationFollowUp::openStatuses();
        $total = ServiceRecommendationFollowUp::query()->count();
        $converted = ServiceRecommendationFollowUp::query()->where('status', ServiceRecommendationFollowUp::STATUS_CONVERTED)->count();

        return [
            'open' => ServiceRecommendationFollowUp::query()->whereIn('status', $open)->count(),
            'today' => ServiceRecommendationFollowUp::query()->whereDate('follow_up_at', $today)->count(),
            'upcoming' => ServiceRecommendationFollowUp::query()->whereIn('status', $open)->whereDate('follow_up_at', '>', $today)->count(),
            'safety' => ServiceRecommendationFollowUp::query()->whereIn('status', $open)->where('recommendation_category', ServiceRecommendationFollowUp::CATEGORY_SAFETY)->count(),
            'converted' => $converted,
            'lost' => ServiceRecommendationFollowUp::query()->where('status', ServiceRecommendationFollowUp::STATUS_LOST)->count(),
            'conversion_rate' => $total > 0 ? round($converted / $total * 100, 1) : 0.0,
            'revenue' => (float) ServiceRecommendationFollowUp::query()->where('status', ServiceRecommendationFollowUp::STATUS_CONVERTED)->sum('estimated_value'),
        ];
    }

    /**
     * @return Builder<ServiceRecommendationFollowUp>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return ServiceRecommendationFollowUp::query()
            ->with(['customer:id,first_name,last_name', 'customerVehicle:id,registration_no', 'followUpBy:id,name'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('recommendation_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('recommended_service', '%'.$search.'%', caseSensitive: false);
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->categoryFilter !== 'all', fn ($q) => $q->where('recommendation_category', $this->categoryFilter));
    }

    /** Stream the Service Recommended Follow-Up Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('service_recommendation_follow_up.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'service-recommended-follow-up-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = ServiceRecommendationFollowUp::recommendationTypes();
        $categories = ServiceRecommendationFollowUp::recommendationCategories();
        $statuses = ServiceRecommendationFollowUp::statuses();

        return response()->streamDownload(function () use ($rows, $types, $categories, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Recommendation No', 'Customer', 'Vehicle', 'Service', 'Type', 'Category', 'Est. Value', 'Status', 'Follow-up By']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->recommendation_no,
                    trim(($r->customer?->first_name ?? '').' '.($r->customer?->last_name ?? '')),
                    $r->customerVehicle?->registration_no,
                    $r->recommended_service,
                    $types[$r->recommendation_type] ?? '',
                    $categories[$r->recommendation_category] ?? '',
                    $r->estimated_value !== null ? number_format((float) $r->estimated_value, 2, '.', '') : '',
                    $statuses[$r->status] ?? $r->status,
                    $r->followUpBy?->name,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('service-recommendation-follow-up::index', [
            'rows' => $rows,
            'statuses' => ServiceRecommendationFollowUp::statuses(),
            'categories' => ServiceRecommendationFollowUp::recommendationCategories(),
            'kpis' => $this->kpis(),
        ]);
    }
}
