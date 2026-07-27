<?php

namespace App\Modules\SurveyorInspection\Livewire;

use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\SurveyorInspection\Models\SurveyorInspection;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Surveyor Inspection')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

    #[Url(as: 'company')]
    public string $companyFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'inspection_no', 'status', 'created_at', 'surveyed_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCompanyFilter(): void
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
        $this->authorize('surveyor_inspection.delete');

        SurveyorInspection::findOrFail($id)->delete();

        Flux::toast(text: 'Surveyor inspection #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'typeFilter', 'companyFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function companies()
    {
        return InsuranceCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return array{pending:int, in_progress:int, completed:int, cancelled:int}
     */
    protected function kpis(): array
    {
        $counts = SurveyorInspection::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        return [
            'pending' => $get(SurveyorInspection::STATUS_PENDING),
            'in_progress' => $get(SurveyorInspection::STATUS_IN_PROGRESS),
            'completed' => $get(SurveyorInspection::STATUS_COMPLETED),
            'cancelled' => $get(SurveyorInspection::STATUS_CANCELLED),
        ];
    }

    /**
     * @return Builder<SurveyorInspection>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return SurveyorInspection::query()
            ->with([
                'jobCard:id,job_card_no',
                'customerVehicle:id,registration_no',
                'insuranceCompany:id,name',
                'salesEstimate:id,estimate_no',
            ])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('inspection_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('surveyor_name', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('survey_type', $this->typeFilter))
            ->when($this->companyFilter !== 'all', fn ($q) => $q->where('insurance_company_id', (int) $this->companyFilter));
    }

    /** Stream the Surveyor Inspection Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('surveyor_inspection.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'surveyor-inspection-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = SurveyorInspection::surveyTypes();
        $approvals = SurveyorInspection::approvalOutcomes();
        $statuses = SurveyorInspection::statuses();

        return response()->streamDownload(function () use ($rows, $types, $approvals, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Inspection No', 'Job Card', 'Vehicle', 'Insurer', 'Estimate', 'Surveyor', 'Survey Type', 'Approval', 'Lines', 'Status', 'Surveyed At']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->inspection_no,
                    $r->jobCard?->job_card_no,
                    $r->customerVehicle?->registration_no,
                    $r->insuranceCompany?->name,
                    $r->salesEstimate?->estimate_no,
                    $r->surveyor_name,
                    $types[$r->survey_type] ?? '',
                    $approvals[$r->surveyor_approval] ?? '',
                    $r->items_count,
                    $statuses[$r->status] ?? $r->status,
                    $r->surveyed_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('surveyor-inspection::index', [
            'rows' => $rows,
            'statuses' => SurveyorInspection::statuses(),
            'surveyTypes' => SurveyorInspection::surveyTypes(),
            'approvalOutcomes' => SurveyorInspection::approvalOutcomes(),
            'kpis' => $this->kpis(),
        ]);
    }
}
