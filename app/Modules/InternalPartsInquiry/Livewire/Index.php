<?php

namespace App\Modules\InternalPartsInquiry\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiry;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Internal Parts Inquiry')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'requested')]
    public string $requestedByFilter = 'all';

    #[Url(as: 'target')]
    public string $targetFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'requested_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'ipi_no', 'requested_at', 'status', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRequestedByFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTargetFilter(): void
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
        $this->authorize('internal_parts_inquiry.delete');

        InternalPartsInquiry::findOrFail($id)->delete();

        Flux::toast(text: 'IPI #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'requestedByFilter', 'targetFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = InternalPartsInquiry::query()
            ->with([
                'jobCard:id,job_card_no,customer_id,customer_vehicle_id',
                'jobCard.customer:id,first_name,last_name',
                'jobCard.customerVehicle:id,registration_no',
                'requestedBy:id,name',
                'target:id,name',
            ])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('ipi_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->requestedByFilter !== 'all', fn ($q) => $q->where('requested_by_employee_id', (int) $this->requestedByFilter))
            ->when($this->targetFilter !== 'all', fn ($q) => $q->where('target_employee_id', (int) $this->targetFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('internal-parts-inquiry::index', [
            'rows' => $rows,
            'statuses' => InternalPartsInquiry::statuses(),
        ]);
    }
}
