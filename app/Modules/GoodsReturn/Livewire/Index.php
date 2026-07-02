<?php

namespace App\Modules\GoodsReturn\Livewire;

use App\Modules\GoodsReturn\Models\GoodsReturn;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Goods Returns')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'return_no', 'grand_total', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
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
        $this->authorize('goods_return.delete');

        GoodsReturn::findOrFail($id)->delete();

        Flux::toast(text: 'Goods return #'.$id.' deleted.', variant: 'success');
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = GoodsReturn::query()
            ->with(['vendor:id,name', 'creditNoteReason:id,name'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('return_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('grn_reference', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('vendor', fn ($v) => $v->whereLike('name', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('document_type', $this->typeFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('goods-return::index', [
            'rows' => $rows,
            'documentTypes' => GoodsReturn::documentTypes(),
        ]);
    }
}
