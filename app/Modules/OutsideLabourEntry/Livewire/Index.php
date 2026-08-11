<?php

namespace App\Modules\OutsideLabourEntry\Livewire;

use App\Modules\OutsideLabourEntry\Models\OutsideLabourEntry;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Outside Labour Entries')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'entry_no', 'grand_total', 'created_at'];

    public function updatingSearch(): void
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
        $this->authorize('outside_labour_entry.delete');

        OutsideLabourEntry::findOrFail($id)->delete();

        Flux::toast(text: 'Entry #'.$id.' deleted.', variant: 'success');
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = OutsideLabourEntry::query()
            ->with(['vendor:id,name', 'jobCard:id,job_card_no'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('outside-labour-entry::index', ['rows' => $rows]);
    }
}
