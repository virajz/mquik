<?php

namespace App\Modules\OutsideLabourEntry\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\LossReasonMaster\Models\LossReasonMaster;
use App\Modules\OutsideLabourEntry\Models\OutsideLabourEntry;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\TransportModeMaster\Models\TransportModeMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Outside Labour Entry')]
class Edit extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $entry_no = null;

    public ?int $vendor_id = null;

    public ?int $vendor_type_id = null;

    public ?int $job_card_id = null;

    public ?int $department_id = null;

    public ?int $advisor_id = null;

    public ?int $technician_id = null;

    public ?int $loss_reason_id = null;

    public ?int $transport_mode_id = null;

    public ?string $outside_work_order_ref = null;

    public ?string $invoice_no = null;

    public ?string $invoice_date = null;

    public ?string $notes = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    #[Url(as: 'from-job-card')]
    public ?int $fromJobCard = null;

    /** @var list<array<string, mixed>> */
    public array $items = [];

    public string $newAttachmentType = 'invoice';

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachmentFiles = [];

    /** @var list<int> */
    public array $removedAttachmentIds = [];

    public function mount(?OutsideLabourEntry $outsideLabourEntry = null): void
    {
        if ($outsideLabourEntry && $outsideLabourEntry->exists) {
            $this->load($outsideLabourEntry);

            return;
        }

        if ($this->fromJobCard) {
            $this->job_card_id = $this->fromJobCard;
        }
    }

    protected function load(OutsideLabourEntry $o): void
    {
        $o->load('items');

        $this->editingId = $o->id;
        foreach ([
            'entry_no', 'vendor_id', 'vendor_type_id', 'job_card_id', 'department_id', 'advisor_id', 'technician_id',
            'loss_reason_id', 'transport_mode_id', 'outside_work_order_ref', 'invoice_no', 'notes',
        ] as $k) {
            $this->{$k} = $o->{$k};
        }
        $this->invoice_date = $o->invoice_date?->format('Y-m-d');

        $this->items = $o->items->map(fn ($i) => [
            'id' => $i->id,
            'line_type' => $i->line_type,
            'spare_id' => $i->spare_id,
            'labour_id' => $i->labour_id,
            'tax_id' => $i->tax_id,
            'description' => $i->description,
            'hsn_code' => $i->hsn_code,
            'qty' => (float) $i->qty,
            'unit_rate' => (float) $i->unit_rate,
            'tax_percent' => (float) $i->tax_percent,
            'sequence_no' => (int) $i->sequence_no,
        ])->all();
    }

    public function updated(string $name, $value): void
    {
        if (preg_match('/^items\.(\d+)\.spare_id$/', $name, $m) && $value) {
            $this->prefillSpare((int) $m[1], (int) $value);
        }
        if (preg_match('/^items\.(\d+)\.labour_id$/', $name, $m) && $value) {
            $this->prefillLabour((int) $m[1], (int) $value);
        }
    }

    protected function prefillSpare(int $i, int $id): void
    {
        $spare = SpareMaster::with('tax')->find($id);
        if (! $spare) {
            return;
        }
        $this->items[$i]['description'] = $spare->name;
        $this->items[$i]['hsn_code'] = $spare->hsn_code;
        $this->items[$i]['unit_rate'] = (float) $spare->rate_before_tax;
        $this->items[$i]['tax_id'] = $spare->tax_id;
        $this->items[$i]['tax_percent'] = (float) (($spare->tax?->gst_percent ?? 0) + ($spare->tax?->cess_percent ?? 0));
    }

    protected function prefillLabour(int $i, int $id): void
    {
        $labour = LabourMaster::with('tax')->find($id);
        if (! $labour) {
            return;
        }
        $this->items[$i]['description'] = $labour->name;
        $this->items[$i]['hsn_code'] = $labour->hsn_sac_code;
        $this->items[$i]['unit_rate'] = (float) $labour->rate_before_tax;
        $this->items[$i]['tax_id'] = $labour->tax_id;
        $this->items[$i]['tax_percent'] = (float) (($labour->tax?->gst_percent ?? 0) + ($labour->tax?->cess_percent ?? 0));
    }

    public function addLine(string $type): void
    {
        $this->items[] = [
            'id' => null,
            'line_type' => $type,
            'spare_id' => null,
            'labour_id' => null,
            'tax_id' => null,
            'description' => '',
            'hsn_code' => null,
            'qty' => 1,
            'unit_rate' => 0,
            'tax_percent' => 0,
            'sequence_no' => count($this->items) + 1,
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function removeAttachment(int $id): void
    {
        if (! in_array($id, $this->removedAttachmentIds, true)) {
            $this->removedAttachmentIds[] = $id;
        }
    }

    /**
     * @return array{parts: float, labour: float, tax: float, grand: float}
     */
    #[Computed]
    public function totals(): array
    {
        $parts = 0.0;
        $labour = 0.0;
        $tax = 0.0;
        foreach ($this->items as $row) {
            $base = (float) ($row['qty'] ?? 0) * (float) ($row['unit_rate'] ?? 0);
            $tax += $base * (float) ($row['tax_percent'] ?? 0) / 100;
            if (($row['line_type'] ?? 'spare') === 'labour') {
                $labour += $base;
            } else {
                $parts += $base;
            }
        }

        return [
            'parts' => round($parts, 2),
            'labour' => round($labour, 2),
            'tax' => round($tax, 2),
            'grand' => round($parts + $labour + $tax, 2),
        ];
    }

    protected function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'vendor_type_id' => ['nullable', 'integer', 'exists:vendor_types,id'],
            'job_card_id' => ['nullable', 'integer', 'exists:job_cards,id'],
            'department_id' => ['nullable', 'integer', 'exists:workshop_departments,id'],
            'advisor_id' => ['nullable', 'integer', 'exists:employees,id'],
            'technician_id' => ['nullable', 'integer', 'exists:employees,id'],
            'loss_reason_id' => ['nullable', 'integer', 'exists:loss_reasons,id'],
            'transport_mode_id' => ['nullable', 'integer', 'exists:transport_modes,id'],
            'outside_work_order_ref' => ['nullable', 'string', 'max:60'],
            'invoice_no' => ['nullable', 'string', 'max:60'],
            'invoice_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array'],
            'items.*.line_type' => ['required', 'in:spare,labour'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['numeric', 'min:0.01'],
            'items.*.unit_rate' => ['numeric', 'min:0'],
            'items.*.tax_percent' => ['numeric', 'min:0', 'max:100'],

            'newAttachmentType' => ['required', Rule::in(array_keys(OutsideLabourEntry::attachmentTypes()))],
            'attachmentFiles.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
        ];
    }

    #[Computed]
    public function vendors()
    {
        return VendorMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function vendorTypes()
    {
        return VendorTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function jobCards()
    {
        return JobCard::query()->orderByDesc('opened_at')->limit(100)->get(['id', 'job_card_no']);
    }

    #[Computed]
    public function departments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function lossReasons()
    {
        return LossReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function transportModes()
    {
        return TransportModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function spares()
    {
        return SpareMaster::query()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name']);
    }

    #[Computed]
    public function labours()
    {
        return LabourMaster::query()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name']);
    }

    #[Computed]
    public function taxes()
    {
        return TaxMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function existingAttachments()
    {
        if (! $this->editingId) {
            return collect();
        }

        return OutsideLabourEntry::findOrFail($this->editingId)->attachments()
            ->whereNotIn('id', $this->removedAttachmentIds)
            ->get();
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'outside_labour_entry.update' : 'outside_labour_entry.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        unset($data['items'], $data['attachmentFiles'], $data['newAttachmentType']);

        $totals = $this->totals();
        $data['parts_total'] = $totals['parts'];
        $data['labour_total'] = $totals['labour'];
        $data['tax_total'] = $totals['tax'];
        $data['grand_total'] = $totals['grand'];

        foreach (['invoice_no', 'outside_work_order_ref', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $entry = DB::transaction(function () use ($data, $items, $isCreate) {
            if ($isCreate) {
                $row = OutsideLabourEntry::create($data);
                $this->editingId = $row->id;
                $this->entry_no = $row->fresh()->entry_no;
            } else {
                $row = OutsideLabourEntry::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncAttachments($row);

            return $row;
        });

        $this->attachmentFiles = [];
        $this->removedAttachmentIds = [];

        Flux::toast(text: 'Entry '.$entry->fresh()->entry_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        if ($isCreate) {
            return redirect()->route('outside-labour-entry.edit', $entry->id);
        }

        return redirect()->route('outside-labour-entry.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncItems(OutsideLabourEntry $entry, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $local = $this->items[$i] ?? [];
            $base = (float) ($row['qty'] ?? 0) * (float) ($row['unit_rate'] ?? 0);
            $lineTotal = round($base * (1 + (float) ($row['tax_percent'] ?? 0) / 100), 2);

            $payload = [
                'line_type' => $row['line_type'],
                'spare_id' => $row['line_type'] === 'spare' ? ($local['spare_id'] ?? null) : null,
                'labour_id' => $row['line_type'] === 'labour' ? ($local['labour_id'] ?? null) : null,
                'tax_id' => $local['tax_id'] ?? null,
                'description' => strtoupper((string) $row['description']),
                'hsn_code' => $local['hsn_code'] ?? null,
                'qty' => (float) ($row['qty'] ?? 1),
                'unit_rate' => (float) ($row['unit_rate'] ?? 0),
                'tax_percent' => (float) ($row['tax_percent'] ?? 0),
                'line_total' => $lineTotal,
                'sequence_no' => $i + 1,
            ];

            if (! empty($local['id'])) {
                $existing = $entry->items()->whereKey($local['id'])->first();
                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = $entry->items()->create($payload);
            $keptIds[] = $created->id;
            $this->items[$i]['id'] = $created->id;
        }

        $entry->items()->whereNotIn('id', $keptIds)->delete();
    }

    protected function syncAttachments(OutsideLabourEntry $entry): void
    {
        if ($this->removedAttachmentIds) {
            foreach ($entry->attachments()->whereIn('id', $this->removedAttachmentIds)->get() as $att) {
                Storage::disk('public')->delete($att->path);
                $att->delete();
            }
        }

        foreach ($this->attachmentFiles as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }
            $entry->attachments()->create([
                'attachment_type' => $this->newAttachmentType,
                'path' => $file->store("outside-labour-entries/{$entry->id}/attachments", 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        }
    }

    public function render()
    {
        return view('outside-labour-entry::edit');
    }
}
