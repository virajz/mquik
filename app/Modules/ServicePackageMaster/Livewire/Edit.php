<?php

namespace App\Modules\ServicePackageMaster\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\ServicePackageMaster\Models\ServicePackageAttachment;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\ServicePackageTypeMaster\Models\ServicePackageTypeMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Service Package')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public ?int $service_package_type_id = null;

    public ?string $description = null;

    public ?string $terms_conditions = null;

    public ?string $usage_rule = null;

    public bool $is_amc = false;

    public ?int $validity_months = null;

    public ?int $validity_km = null;

    public float $total_price = 0;

    public ?float $net_price = null;

    public ?float $offer_price = null;

    public ?float $discount_percent = null;

    public ?float $saving_price = null;

    public ?string $remarks = null;

    public bool $is_active = true;

    /** @var list<array<string, mixed>> */
    public array $services = [];

    /** @var list<array<string, mixed>> */
    public array $spares = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:32', Rule::unique('service_packages', 'code')->ignore($this->editingId)],
            'service_package_type_id' => ['nullable', 'integer', Rule::exists('service_package_types', 'id')->where('is_active', true)],
            'description' => ['nullable', 'string', 'max:1000'],
            'terms_conditions' => ['nullable', 'string', 'max:5000'],
            'usage_rule' => ['nullable', Rule::in(array_keys(ServicePackageMaster::usageRules()))],
            'is_amc' => ['boolean'],
            'validity_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'validity_km' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'total_price' => ['numeric', 'min:0', 'max:9999999.99'],
            'net_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'offer_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'saving_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],

            'services' => ['array'],
            'services.*.service_type_id' => ['required', 'integer', Rule::exists('service_types', 'id')->where('is_active', true)],
            'services.*.sequence_no' => ['integer', 'min:1', 'max:99'],
            'services.*.sac' => ['nullable', 'string', 'max:12'],
            'services.*.rate' => ['nullable', 'numeric', 'min:0'],
            'services.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'services.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'services.*.taxable_value' => ['nullable', 'numeric', 'min:0'],
            'services.*.net_price' => ['nullable', 'numeric', 'min:0'],
            'services.*.offer_price' => ['nullable', 'numeric', 'min:0'],
            'services.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'services.*.saving_price' => ['nullable', 'numeric', 'min:0'],
            'services.*.due_after_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'services.*.due_after_km' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'services.*.notes' => ['nullable', 'string', 'max:255'],

            'spares' => ['array'],
            'spares.*.spare_id' => ['nullable', 'integer', Rule::exists('spares', 'id')],
            'spares.*.uom_id' => ['nullable', 'integer', Rule::exists('units_of_measure', 'id')],
            'spares.*.hsn_id' => ['nullable', 'integer', Rule::exists('hsn_codes', 'id')],
            'spares.*.tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')],
            'spares.*.description' => ['required', 'string', 'max:255'],
            'spares.*.sac' => ['nullable', 'string', 'max:12'],
            'spares.*.rate' => ['nullable', 'numeric', 'min:0'],
            'spares.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'spares.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'spares.*.taxable_value' => ['nullable', 'numeric', 'min:0'],
            'spares.*.net_price' => ['nullable', 'numeric', 'min:0'],
            'spares.*.offer_price' => ['nullable', 'numeric', 'min:0'],
            'spares.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'spares.*.saving_price' => ['nullable', 'numeric', 'min:0'],
            'spares.*.remarks' => ['nullable', 'string', 'max:255'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(ServicePackageAttachment::attachmentTypes()))],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ];
    }

    public function mount(?ServicePackageMaster $servicePackageMaster = null): void
    {
        if ($servicePackageMaster && $servicePackageMaster->exists) {
            $this->load($servicePackageMaster);
        }
    }

    protected function load(ServicePackageMaster $r): void
    {
        $r->loadMissing(['services', 'spares', 'attachments']);
        $this->editingId = $r->id;
        $this->name = $r->name;
        $this->code = $r->code;
        $this->service_package_type_id = $r->service_package_type_id;
        $this->description = $r->description;
        $this->terms_conditions = $r->terms_conditions;
        $this->usage_rule = $r->usage_rule;
        $this->is_amc = (bool) $r->is_amc;
        $this->validity_months = $r->validity_months;
        $this->validity_km = $r->validity_km;
        $this->total_price = (float) $r->total_price;
        $this->net_price = $r->net_price === null ? null : (float) $r->net_price;
        $this->offer_price = $r->offer_price === null ? null : (float) $r->offer_price;
        $this->discount_percent = $r->discount_percent === null ? null : (float) $r->discount_percent;
        $this->saving_price = $r->saving_price === null ? null : (float) $r->saving_price;
        $this->remarks = $r->remarks;
        $this->is_active = (bool) $r->is_active;

        $this->services = $r->services
            ->map(fn ($s) => [
                'id' => $s->id,
                'service_type_id' => $s->service_type_id,
                'sequence_no' => (int) $s->sequence_no,
                'sac' => $s->sac,
                'rate' => $s->rate,
                'quantity' => $s->quantity,
                'tax_percent' => $s->tax_percent,
                'taxable_value' => $s->taxable_value,
                'net_price' => $s->net_price,
                'offer_price' => $s->offer_price,
                'discount_percent' => $s->discount_percent,
                'saving_price' => $s->saving_price,
                'due_after_months' => $s->due_after_months,
                'due_after_km' => $s->due_after_km,
                'notes' => $s->notes,
            ])
            ->values()
            ->all();

        $this->spares = $r->spares
            ->map(fn ($s) => [
                'id' => $s->id,
                'spare_id' => $s->spare_id,
                'uom_id' => $s->uom_id,
                'hsn_id' => $s->hsn_id,
                'tax_id' => $s->tax_id,
                'description' => $s->description,
                'sac' => $s->sac,
                'rate' => $s->rate,
                'quantity' => $s->quantity,
                'tax_percent' => $s->tax_percent,
                'taxable_value' => $s->taxable_value,
                'net_price' => $s->net_price,
                'offer_price' => $s->offer_price,
                'discount_percent' => $s->discount_percent,
                'saving_price' => $s->saving_price,
                'remarks' => $s->remarks,
                'spareSearch' => '',
            ])
            ->values()
            ->all();

        $this->attachments = $r->attachments->map(fn ($x) => [
            'id' => $x->id, 'attachment_type' => $x->attachment_type, 'path' => $x->path,
            'original_name' => $x->original_name, 'notes' => $x->notes,
        ])->all();
    }

    public function addService(): void
    {
        $this->services[] = [
            'id' => null,
            'service_type_id' => null,
            'sequence_no' => count($this->services) + 1,
            'sac' => null,
            'rate' => null,
            'quantity' => 1,
            'tax_percent' => null,
            'taxable_value' => null,
            'net_price' => null,
            'offer_price' => null,
            'discount_percent' => null,
            'saving_price' => null,
            'due_after_months' => null,
            'due_after_km' => null,
            'notes' => null,
        ];
    }

    public function removeService(int $index): void
    {
        if (! isset($this->services[$index])) {
            return;
        }
        unset($this->services[$index]);
        $this->services = array_values($this->services);
    }

    /** @return array<string, mixed> */
    protected function blankSpare(): array
    {
        return [
            'id' => null, 'spare_id' => null, 'uom_id' => null, 'hsn_id' => null, 'tax_id' => null,
            'description' => '', 'sac' => null, 'rate' => null, 'quantity' => 1, 'tax_percent' => null,
            'taxable_value' => null, 'net_price' => null, 'offer_price' => null, 'discount_percent' => null,
            'saving_price' => null, 'remarks' => null, 'spareSearch' => '',
        ];
    }

    public function addSpare(): void
    {
        $this->spares[] = $this->blankSpare();
    }

    public function removeSpare(int $index): void
    {
        if (! isset($this->spares[$index])) {
            return;
        }
        unset($this->spares[$index]);
        $this->spares = array_values($this->spares);
    }

    /** Auto-fill a spare line from the picked spare. */
    public function updatedSpares($value, $key): void
    {
        if (! str_ends_with($key, '.spare_id')) {
            return;
        }

        $index = (int) explode('.', $key)[0];
        $spareId = $this->spares[$index]['spare_id'] ?? null;
        if (! $spareId) {
            return;
        }

        $spare = SpareMaster::find($spareId);
        if (! $spare) {
            return;
        }

        $this->spares[$index]['uom_id'] = $spare->uom_id;
        $this->spares[$index]['hsn_id'] = $spare->hsn_id;
        $this->spares[$index]['tax_id'] = $spare->tax_id;
        if (blank($this->spares[$index]['description'])) {
            $this->spares[$index]['description'] = $spare->name;
        }
    }

    public function addAttachment(): void
    {
        $this->attachments[] = ['id' => null, 'attachment_type' => null, 'path' => null, 'original_name' => null, 'notes' => null];
    }

    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index], $this->attachmentFiles[$index]);
        $this->attachments = array_values($this->attachments);
    }

    #[Computed]
    public function serviceTypes()
    {
        return ServiceTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function packageTypes()
    {
        return ServicePackageTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function uoms()
    {
        return UnitOfMeasureMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
    }

    #[Computed]
    public function taxes()
    {
        return TaxMaster::query()->where('is_active', true)->orderBy('gst_percent')->get(['id', 'name', 'gst_percent']);
    }

    public function spareOptions(int $index)
    {
        return $this->pickerOptions(
            query: SpareMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'spare_code'], term: $this->spares[$index]['spareSearch'] ?? '',
            selected: $this->spares[$index]['spare_id'] ?? null, columns: ['id', 'name', 'spare_code'], limit: 30,
        );
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'service_package_master.update' : 'service_package_master.create');

        // Strip blank line-item rows so an unfilled trailing row doesn't fail validation.
        $this->services = array_values(array_filter(
            $this->services,
            fn ($s) => filled($s['service_type_id'] ?? null),
        ));
        $this->spares = array_values(array_filter(
            $this->spares,
            fn ($s) => filled($s['description'] ?? null) || filled($s['spare_id'] ?? null),
        ));

        $data = $this->validate();
        $services = $data['services'] ?? [];
        $spares = $data['spares'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['services'], $data['spares'], $data['attachments'], $data['attachmentFiles']);

        $skip = ['is_active', 'is_amc', 'usage_rule', 'total_price', 'net_price', 'offer_price', 'discount_percent', 'saving_price', 'validity_months', 'validity_km'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        $package = DB::transaction(function () use ($data, $services, $spares, $attachments) {
            if ($this->editingId) {
                $p = ServicePackageMaster::findOrFail($this->editingId);
                $p->update($data);
            } else {
                $p = ServicePackageMaster::create($data);
                $this->editingId = $p->id;
            }

            $this->syncServices($p, $services);
            $this->syncSpares($p, $spares);
            $this->syncAttachments($p, $attachments);

            return $p;
        });

        $this->attachmentFiles = [];

        Flux::toast(
            text: 'Package #'.$package->id.' saved.',
            variant: 'success',
        );

        return redirect()->route('service-package-master.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncServices(ServicePackageMaster $package, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = $package->services()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'service_type_id' => (int) $row['service_type_id'],
                    'sequence_no' => (int) ($row['sequence_no'] ?? $i + 1),
                    'sac' => isset($row['sac']) && is_string($row['sac']) ? strtoupper($row['sac']) : null,
                    'rate' => $row['rate'] ?? null,
                    'quantity' => $row['quantity'] ?? 1,
                    'tax_percent' => $row['tax_percent'] ?? null,
                    'taxable_value' => $row['taxable_value'] ?? null,
                    'net_price' => $row['net_price'] ?? null,
                    'offer_price' => $row['offer_price'] ?? null,
                    'discount_percent' => $row['discount_percent'] ?? null,
                    'saving_price' => $row['saving_price'] ?? null,
                    'due_after_months' => $row['due_after_months'] ?? null,
                    'due_after_km' => $row['due_after_km'] ?? null,
                    'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
                ],
            )->id;
        }

        $package->services()->whereKeyNot($keptIds)->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncSpares(ServicePackageMaster $package, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = $package->spares()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'spare_id' => $row['spare_id'] ?: null,
                    'uom_id' => $row['uom_id'] ?: null,
                    'hsn_id' => $row['hsn_id'] ?: null,
                    'tax_id' => $row['tax_id'] ?: null,
                    'description' => strtoupper(trim((string) $row['description'])),
                    'sac' => isset($row['sac']) && is_string($row['sac']) ? strtoupper($row['sac']) : null,
                    'rate' => $row['rate'] ?? null,
                    'quantity' => $row['quantity'] ?? 1,
                    'tax_percent' => $row['tax_percent'] ?? null,
                    'taxable_value' => $row['taxable_value'] ?? null,
                    'net_price' => $row['net_price'] ?? null,
                    'offer_price' => $row['offer_price'] ?? null,
                    'discount_percent' => $row['discount_percent'] ?? null,
                    'saving_price' => $row['saving_price'] ?? null,
                    'remarks' => isset($row['remarks']) && is_string($row['remarks']) ? strtoupper($row['remarks']) : null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $package->spares()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(ServicePackageMaster $package, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('service-packages/'.$package->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $package->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $package->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('service-package-master::edit');
    }
}
