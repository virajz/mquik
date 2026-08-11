<?php

namespace App\Modules\DocumentCollection\Livewire;

use App\Concerns\ScopesToRecord;
use App\Modules\DocumentCollection\Models\DocumentCollection;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Document Collection')]
class Index extends Component
{
    use ScopesToRecord;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'source')]
    public string $requestTypeFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    /** Whitelist sortable columns — never trust the URL. */
    protected array $sortable = ['id', 'doc_collection_no', 'status', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRequestTypeFilter(): void
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
        $this->authorize('document_collection.delete');

        DocumentCollection::findOrFail($id)->delete();
        Flux::toast(text: 'Document collection deleted.', variant: 'success');
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = DocumentCollection::query()
            ->with([
                'customer:id,first_name,last_name,phone',
                'customerVehicle:id,registration_no',
                'insuranceCompany:id,name',
            ])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->requestTypeFilter !== 'all', fn ($q) => $q->where('request_type', $this->requestTypeFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->tap(fn ($q) => $this->applyRecordScope($q))
            ->paginate(20);

        return view('document-collection::index', [
            'rows' => $rows,
            'statuses' => DocumentCollection::statuses(),
            'requestTypes' => DocumentCollection::requestTypes(),
        ]);
    }
}
