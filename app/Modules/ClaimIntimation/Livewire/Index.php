<?php

namespace App\Modules\ClaimIntimation\Livewire;

use App\Modules\ClaimIntimation\Models\ClaimIntimation;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
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
#[Title('Claim Intimation')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'company')]
    public string $companyFilter = 'all';

    #[Url(as: 'tat')]
    public string $tatFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'intimation_no', 'status', 'created_at', 'intimated_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCompanyFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTatFilter(): void
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
        $this->authorize('claim_intimation.delete');

        ClaimIntimation::findOrFail($id)->delete();

        Flux::toast(text: 'Claim intimation #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'companyFilter', 'tatFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function companies()
    {
        return InsuranceCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Dashboard counters.
     *
     * @return array{pending:int, intimated:int, cancelled:int, awaiting_survey:int}
     */
    protected function kpis(): array
    {
        $counts = ClaimIntimation::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        return [
            'pending' => $get(ClaimIntimation::STATUS_PENDING),
            'intimated' => $get(ClaimIntimation::STATUS_INTIMATED),
            'cancelled' => $get(ClaimIntimation::STATUS_CANCELLED),
            // Intimated claims still needing a survey (proxy: intimated, survey TAT set).
            'awaiting_survey' => ClaimIntimation::query()
                ->where('status', ClaimIntimation::STATUS_INTIMATED)
                ->whereNotNull('survey_tat')
                ->count(),
        ];
    }

    /**
     * @return Builder<ClaimIntimation>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return ClaimIntimation::query()
            ->with([
                'jobCard:id,job_card_no',
                'customerVehicle:id,registration_no',
                'insuranceCompany:id,name',
                'claimType:id,name',
            ])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->companyFilter !== 'all', fn ($q) => $q->where('insurance_company_id', (int) $this->companyFilter))
            ->when($this->tatFilter !== 'all', fn ($q) => $q->where('survey_tat', $this->tatFilter));
    }

    /** Stream the filtered list as the Survey Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('claim_intimation.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'survey-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $tats = ClaimIntimation::surveyTats();
        $statuses = ClaimIntimation::statuses();

        return response()->streamDownload(function () use ($rows, $tats, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Intimation No', 'Job Card', 'Vehicle', 'Insurer', 'Policy No', 'Claim Type', 'Claim No', 'Survey TAT', 'Status', 'Intimated At']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->intimation_no,
                    $r->jobCard?->job_card_no,
                    $r->customerVehicle?->registration_no,
                    $r->insuranceCompany?->name,
                    $r->policy_no,
                    $r->claimType?->name,
                    $r->claim_no,
                    $tats[$r->survey_tat] ?? '',
                    $statuses[$r->status] ?? $r->status,
                    $r->intimated_at?->format('Y-m-d H:i'),
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

        return view('claim-intimation::index', [
            'rows' => $rows,
            'statuses' => ClaimIntimation::statuses(),
            'surveyTats' => ClaimIntimation::surveyTats(),
            'kpis' => $this->kpis(),
        ]);
    }
}
