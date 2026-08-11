<?php

namespace App\Modules\PolicyRenewalFollowUp\Livewire;

use App\Modules\PolicyRenewalFollowUp\Models\PolicyRenewalFollowUp;
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
#[Title('Policy Renewal Follow-Ups')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'retention')]
    public string $retentionFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'policy_end_date';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    protected array $sortable = ['id', 'follow_up_no', 'status', 'policy_end_date', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRetentionFilter(): void
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
        $this->authorize('policy_renewal_follow_up.delete');
        PolicyRenewalFollowUp::findOrFail($id)->delete();
        Flux::toast(text: 'Renewal follow-up #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'retentionFilter']);
        $this->resetPage();
    }

    /**
     * @return array{expiring_week:int, overdue:int, today:int, lost:int, revenue:float}
     */
    protected function kpis(): array
    {
        $today = Carbon::today();
        $open = PolicyRenewalFollowUp::openStatuses();

        return [
            'expiring_week' => PolicyRenewalFollowUp::query()->whereIn('status', $open)
                ->whereBetween('policy_end_date', [$today, $today->copy()->addDays(7)])->count(),
            'overdue' => PolicyRenewalFollowUp::query()
                ->whereIn('status', [PolicyRenewalFollowUp::STATUS_OVERDUE, ...$open])
                ->whereDate('policy_end_date', '<', $today)->count(),
            'today' => PolicyRenewalFollowUp::query()->whereDate('follow_up_at', $today)->count(),
            'lost' => PolicyRenewalFollowUp::query()->where('status', PolicyRenewalFollowUp::STATUS_LOST)->count(),
            'revenue' => (float) PolicyRenewalFollowUp::query()->where('status', PolicyRenewalFollowUp::STATUS_RENEWED)->sum('renewal_premium'),
        ];
    }

    /**
     * @return Builder<PolicyRenewalFollowUp>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return PolicyRenewalFollowUp::query()
            ->with(['customer:id,first_name,last_name', 'customerVehicle:id,registration_no', 'insuranceCompany:id,name'])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->retentionFilter !== 'all', fn ($q) => $q->where('customer_retention', $this->retentionFilter));
    }

    /** Stream the Ins. Policy Renewal Due Follow-Up Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('policy_renewal_follow_up.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'policy-renewal-follow-up-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $statuses = PolicyRenewalFollowUp::statuses();
        $responses = PolicyRenewalFollowUp::customerResponses();

        return response()->streamDownload(function () use ($rows, $statuses, $responses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Follow-up No', 'Customer', 'Vehicle', 'Policy No', 'Insurer', 'Expiry', 'Response', 'Status', 'Premium']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->follow_up_no,
                    trim(($r->customer?->first_name ?? '').' '.($r->customer?->last_name ?? '')),
                    $r->customerVehicle?->registration_no,
                    $r->policy_number,
                    $r->insuranceCompany?->name,
                    $r->policy_end_date?->format('Y-m-d'),
                    $responses[$r->customer_response] ?? '',
                    $statuses[$r->status] ?? $r->status,
                    $r->renewal_premium !== null ? number_format((float) $r->renewal_premium, 2, '.', '') : '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('policy-renewal-follow-up::index', [
            'rows' => $rows,
            'statuses' => PolicyRenewalFollowUp::statuses(),
            'retentions' => PolicyRenewalFollowUp::retentions(),
            'kpis' => $this->kpis(),
            'today' => Carbon::today(),
        ]);
    }
}
