<?php

namespace App\Modules\DigitalInspection\Livewire;

use App\Concerns\ScopesToRecord;
use App\Modules\DigitalInspection\Models\DigitalInspection;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Digital Inspections')]
class Index extends Component
{
    use ScopesToRecord;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'tech')]
    public string $technicianFilter = 'all';

    #[Url(as: 'template')]
    public string $templateFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'inspection_no', 'status', 'created_at', 'started_at', 'completed_at', 'tat_seconds'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTechnicianFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTemplateFilter(): void
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
        $this->authorize('digital_inspection.delete');

        DigitalInspection::findOrFail($id)->delete();

        Flux::toast(text: 'Inspection #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'technicianFilter', 'templateFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function technicians()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function templates()
    {
        return InspectionTemplateMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'applies_to']);
    }

    /**
     * Elapsed seconds between the two stamps.
     *
     * `extract(epoch from …)` is Postgres-only and the test suite runs on
     * SQLite, so each driver gets the expression it understands.
     */
    protected function tatSecondsExpression(): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? 'extract(epoch from (completed_at - started_at))'
            : '(julianday(completed_at) - julianday(started_at)) * 86400';
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = DigitalInspection::query()
            ->with([
                'jobCard:id,job_card_no,customer_id,customer_vehicle_id',
                'jobCard.customer:id,first_name,last_name',
                'jobCard.customerVehicle:id,registration_no',
                'template:id,name,applies_to',
                'technician:id,name',
            ])
            ->withCount('items')
            // Technician TAT, worked out in the database so the column sorts.
            ->selectRaw('*, '.$this->tatSecondsExpression().' as tat_seconds')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->technicianFilter !== 'all', fn ($q) => $q->where('assigned_technician_id', (int) $this->technicianFilter))
            ->when($this->templateFilter !== 'all', fn ($q) => $q->where('inspection_template_id', (int) $this->templateFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->tap(fn ($q) => $this->applyRecordScope($q))
            ->paginate(20);

        return view('digital-inspection::index', [
            'rows' => $rows,
            // Filter offers the live statuses; the badge falls back to the
            // retired ones so a historic row still renders its own name.
            'statuses' => DigitalInspection::statuses(),
            'allStatuses' => DigitalInspection::allStatuses(),
        ]);
    }
}
