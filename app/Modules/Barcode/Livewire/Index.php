<?php

namespace App\Modules\Barcode\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\Barcode\Models\BarcodeLabel;
use App\Modules\Barcode\Services\BarcodeService;
use App\Modules\SpareMaster\Models\SpareMaster;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Barcode Labels')]
class Index extends Component
{
    use SearchesPickerOptions;
    use WithPagination;

    /** Search term for the server-backed spares picker. */
    public string $spareSearch = '';

    #[Url(as: 'q')]
    public string $search = '';

    // Generate modal state
    public ?int $selectedSpareId = null;

    public string $barcodeType = BarcodeLabel::TYPE_CODE128;

    public string $labelSize = '50x25';

    public int $copies = 1;

    public bool $isPrimary = false;

    protected array $sortable = ['barcode', 'barcode_type', 'label_size', 'created_at'];

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if (! in_array($column, $this->sortable, true)) {
            return;
        }

        $this->sortBy === $column
            ? $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc'
            : [$this->sortBy = $column, $this->sortDirection = 'asc'];
    }

    public function openGenerate(): void
    {
        $this->reset(['selectedSpareId', 'barcodeType', 'labelSize', 'copies', 'isPrimary']);
        $this->copies = 1;
        $this->barcodeType = BarcodeLabel::TYPE_CODE128;
        $this->labelSize = '50x25';
        Flux::modal('barcode-generate')->show();
    }

    public function generate(): void
    {
        $this->authorize('barcode.create');

        $this->validate([
            'selectedSpareId' => 'required|exists:spares,id',
            'barcodeType' => 'required|in:code128,qr,ean13',
            'labelSize' => 'required|in:50x25,40x20,60x40,100x50',
            'copies' => 'required|integer|min:1|max:100',
        ]);

        $spare = SpareMaster::findOrFail($this->selectedSpareId);

        // Each generate() call creates a new label. The base code is deterministic;
        // append a 2-digit suffix (01, 02, …) so the same spare can have multiple labels.
        $base = BarcodeService::generate($spare->id, $this->barcodeType);
        $suffix = 1;
        $barcodeString = $base;

        while (BarcodeLabel::where('barcode', $barcodeString)->exists()) {
            $barcodeString = $base.str_pad((string) $suffix, 2, '0', STR_PAD_LEFT);
            $suffix++;
        }

        $label = BarcodeLabel::create([
            'spare_id' => $spare->id,
            'barcode' => $barcodeString,
            'barcode_type' => $this->barcodeType,
            'label_size' => $this->labelSize,
            'copies' => $this->copies,
            'is_primary' => $this->isPrimary,
        ]);

        if ($this->isPrimary) {
            BarcodeLabel::where('spare_id', $spare->id)
                ->where('id', '!=', $label->id)
                ->update(['is_primary' => false]);
        }

        Flux::toast(text: 'Barcode label generated for '.$spare->name.'.', variant: 'success');
        Flux::modal('barcode-generate')->close();
        $this->reset(['selectedSpareId']);
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $this->authorize('barcode.delete');

        BarcodeLabel::findOrFail($id)->delete();
        Flux::toast(text: 'Barcode label deleted.', variant: 'success');
    }

    #[Computed]
    public function spares()
    {
        return $this->pickerOptions(
            query: SpareMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'spare_code'],
            term: $this->spareSearch,
            selected: $this->selectedSpareId,
            columns: ['id', 'name', 'spare_code'],
            limit: 30,
        );
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = BarcodeLabel::query()
            ->with('spare')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('barcode', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('spare', fn ($s) => $s->whereLike('name', '%'.$search.'%', caseSensitive: false)
                        ->orWhereLike('spare_code', '%'.$search.'%', caseSensitive: false));
            }))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(25);

        return view('barcode::index', [
            'rows' => $rows,
            'barcodeTypes' => BarcodeLabel::types(),
            'labelSizes' => BarcodeLabel::labelSizes(),
        ]);
    }
}
