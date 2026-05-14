<?php

namespace App\Modules\ServicePackageMaster\Livewire;

use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public ?string $description = null;

    public bool $is_amc = false;

    public ?int $validity_months = null;

    public ?int $validity_km = null;

    public float $total_price = 0;

    public bool $is_active = true;

    /** @var list<array{id: ?int, service_type_id: ?int, sequence_no: int, due_after_months: ?int, due_after_km: ?int, notes: ?string}> */
    public array $services = [];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:32', Rule::unique('service_packages', 'code')->ignore($this->editingId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_amc' => ['boolean'],
            'validity_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'validity_km' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'total_price' => ['numeric', 'min:0', 'max:9999999.99'],
            'is_active' => ['boolean'],

            'services' => ['array'],
            'services.*.service_type_id' => ['required', 'integer', Rule::exists('service_types', 'id')->where('is_active', true)],
            'services.*.sequence_no' => ['integer', 'min:1', 'max:99'],
            'services.*.due_after_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'services.*.due_after_km' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'services.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    #[On('service-package-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $r = ServicePackageMaster::with('services')->findOrFail($id);
        $this->editingId = $r->id;
        $this->name = $r->name;
        $this->code = $r->code;
        $this->description = $r->description;
        $this->is_amc = (bool) $r->is_amc;
        $this->validity_months = $r->validity_months;
        $this->validity_km = $r->validity_km;
        $this->total_price = (float) $r->total_price;
        $this->is_active = (bool) $r->is_active;

        $this->services = $r->services
            ->map(fn ($s) => [
                'id' => $s->id,
                'service_type_id' => $s->service_type_id,
                'sequence_no' => (int) $s->sequence_no,
                'due_after_months' => $s->due_after_months,
                'due_after_km' => $s->due_after_km,
                'notes' => $s->notes,
            ])
            ->values()
            ->all();
    }

    public function addService(): void
    {
        $this->services[] = [
            'id' => null,
            'service_type_id' => null,
            'sequence_no' => count($this->services) + 1,
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

    #[Computed]
    public function serviceTypes()
    {
        return ServiceTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'service_package_master.update' : 'service_package_master.create');

        // Strip blank line-item rows so an unfilled trailing row doesn't fail validation.
        $this->services = array_values(array_filter(
            $this->services,
            fn ($s) => filled($s['service_type_id'] ?? null),
        ));

        $data = $this->validate();
        $services = $data['services'] ?? [];
        unset($data['services']);

        $skip = ['is_active', 'is_amc', 'total_price', 'validity_months', 'validity_km'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        $package = DB::transaction(function () use ($data, $services) {
            if ($this->editingId) {
                $p = ServicePackageMaster::findOrFail($this->editingId);
                $p->update($data);
            } else {
                $p = ServicePackageMaster::create($data);
                $this->editingId = $p->id;
            }

            $this->syncServices($p, $services);

            return $p;
        });

        Flux::toast(
            text: 'Package #'.$package->id.($this->editingId === $package->id ? '' : '').' saved.',
            variant: 'success',
        );

        $this->dispatch('service-package-master:saved');
        $this->resetForm();
        Flux::modal('service-package-master-form')->close();
    }

    /**
     * @param  array<int, array{id?: int|null, service_type_id: int, sequence_no?: int, due_after_months?: int|null, due_after_km?: int|null, notes?: string|null}>  $rows
     */
    protected function syncServices(ServicePackageMaster $package, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $i => $row) {
            $payload = [
                'service_type_id' => (int) $row['service_type_id'],
                'sequence_no' => (int) ($row['sequence_no'] ?? $i + 1),
                'due_after_months' => $row['due_after_months'] ?? null,
                'due_after_km' => $row['due_after_km'] ?? null,
                'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
            ];

            if (! empty($row['id'])) {
                $existing = $package->services()->whereKey($row['id'])->first();
                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = $package->services()->create($payload);
            $keptIds[] = $created->id;
        }

        $package->services()->whereNotIn('id', $keptIds)->delete();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->description = null;
        $this->is_amc = false;
        $this->validity_months = null;
        $this->validity_km = null;
        $this->total_price = 0;
        $this->is_active = true;
        $this->services = [];
    }

    public function render()
    {
        return view('service-package-master::form');
    }
}
