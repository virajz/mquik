<?php

namespace App\Modules\TyreReport\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\TyreReport\Models\TyreReport;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tyre Report')]
class Edit extends Component
{
    use SearchesPickerOptions;

    public ?int $editingId = null;

    public ?string $report_no = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $job_card_id = null;

    public ?int $inspected_by_id = null;

    public ?int $odometer_km = null;

    public string $reported_on = '';

    public ?string $recommendation = null;

    public ?string $notes = null;

    /** Search terms for the server-backed pickers. */
    public string $customerSearch = '';

    public string $vehicleSearch = '';

    /**
     * One row per wheel, always the five fixed positions.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $lines = [];

    public function mount(?TyreReport $tyreReport = null): void
    {
        if ($tyreReport && $tyreReport->exists) {
            $this->load($tyreReport);

            return;
        }

        $this->reported_on = now()->toDateString();
        $this->lines = $this->blankLines();
    }

    protected function load(TyreReport $report): void
    {
        $this->editingId = $report->id;
        $this->report_no = $report->report_no;
        $this->customer_id = $report->customer_id;
        $this->customer_vehicle_id = $report->customer_vehicle_id;
        $this->job_card_id = $report->job_card_id;
        $this->inspected_by_id = $report->inspected_by_id;
        $this->odometer_km = $report->odometer_km;
        $this->reported_on = $report->reported_on?->toDateString() ?? now()->toDateString();
        $this->recommendation = $report->recommendation;
        $this->notes = $report->notes;

        $saved = $report->lines()->get()->keyBy('position');

        $this->lines = collect(TyreReport::POSITIONS)->values()->map(function ($_, $i) use ($saved) {
            $position = array_keys(TyreReport::POSITIONS)[$i];
            $line = $saved->get($position);

            return [
                'position' => $position,
                'tyre_size' => $line?->tyre_size,
                'tyre_make' => $line?->tyre_make,
                'pattern' => $line?->pattern,
                'mfg_week_year' => $line?->mfg_week_year,
                'pressure_psi' => $line?->pressure_psi,
                'tread_depth_mm' => $line?->tread_depth_mm,
                'condition' => $line?->condition ?? TyreReport::CONDITION_OK,
                'has_crack' => (bool) $line?->has_crack,
                'has_bulge' => (bool) $line?->has_bulge,
                'is_worn_out' => (bool) $line?->is_worn_out,
                'has_puncture' => (bool) $line?->has_puncture,
                'notes' => $line?->notes,
            ];
        })->all();
    }

    /** @return array<int, array<string, mixed>> */
    protected function blankLines(): array
    {
        return collect(TyreReport::POSITIONS)->keys()->map(fn ($position) => [
            'position' => $position,
            'tyre_size' => null,
            'tyre_make' => null,
            'pattern' => null,
            'mfg_week_year' => null,
            'pressure_psi' => null,
            'tread_depth_mm' => null,
            'condition' => TyreReport::CONDITION_OK,
            'has_crack' => false,
            'has_bulge' => false,
            'is_worn_out' => false,
            'has_puncture' => false,
            'notes' => null,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'customer_vehicle_id' => [
                'nullable', 'integer',
                Rule::exists('customer_vehicles', 'id')->where(fn ($q) => $this->customer_id
                    ? $q->where('customer_id', $this->customer_id)
                    : $q),
            ],
            'job_card_id' => ['nullable', 'integer', 'exists:job_cards,id'],
            'inspected_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'odometer_km' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'reported_on' => ['required', 'date_format:Y-m-d'],
            'recommendation' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['array', 'size:5'],
            'lines.*.position' => ['required', Rule::in(array_keys(TyreReport::POSITIONS))],
            'lines.*.tyre_size' => ['nullable', 'string', 'max:32'],
            'lines.*.tyre_make' => ['nullable', 'string', 'max:60'],
            'lines.*.pattern' => ['nullable', 'string', 'max:60'],
            'lines.*.mfg_week_year' => ['nullable', 'string', 'max:8'],
            'lines.*.pressure_psi' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tread_depth_mm' => ['nullable', 'numeric', 'min:0', 'max:25'],
            'lines.*.condition' => ['required', Rule::in(array_keys(TyreReport::conditions()))],
            'lines.*.has_crack' => ['boolean'],
            'lines.*.has_bulge' => ['boolean'],
            'lines.*.is_worn_out' => ['boolean'],
            'lines.*.has_puncture' => ['boolean'],
            'lines.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function updatedCustomerId(): void
    {
        // Vehicle almost certainly belonged to the previous customer.
        $this->customer_vehicle_id = null;
    }

    #[Computed]
    public function customers()
    {
        return $this->pickerOptions(
            query: CustomerMaster::query()->where('is_active', true)->orderBy('first_name'),
            searchColumns: ['first_name', 'middle_name', 'last_name', 'phone'],
            term: $this->customerSearch,
            selected: $this->customer_id,
            columns: ['id', 'first_name', 'middle_name', 'last_name', 'phone'],
        );
    }

    #[Computed]
    public function vehicles()
    {
        return $this->pickerOptions(
            query: CustomerVehicleMaster::query()
                ->with('model.brand:id,name')
                ->where('is_active', true)
                ->when($this->customer_id, fn ($q, $id) => $q->where('customer_id', $id)),
            searchColumns: ['registration_no'],
            term: $this->vehicleSearch,
            selected: $this->customer_vehicle_id,
            columns: ['id', 'registration_no', 'model_id', 'customer_id'],
        );
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'tyre_report.update' : 'tyre_report.create');

        $data = $this->validate();
        $lines = $data['lines'];
        unset($data['lines']);

        $data['reported_on'] = Carbon::parse($data['reported_on']);
        foreach (['recommendation', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $report = DB::transaction(function () use ($data, $lines, $isCreate) {
            if ($isCreate) {
                $report = TyreReport::create($data);
            } else {
                $report = TyreReport::findOrFail($this->editingId);
                $report->update($data);
            }

            $this->syncLines($report, $lines);

            return $report;
        });

        if ($isCreate) {
            $this->editingId = $report->id;
            $this->report_no = $report->fresh()->report_no;
        }

        Flux::toast(
            text: 'Tyre Report '.$report->report_no.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        return redirect()->route('tyre-report.index');
    }

    /** @param  array<int, array<string, mixed>>  $lines */
    protected function syncLines(TyreReport $report, array $lines): void
    {
        foreach (array_values($lines) as $i => $line) {
            $report->lines()->updateOrCreate(
                ['position' => $line['position']],
                [
                    'tyre_size' => $this->up($line['tyre_size']),
                    'tyre_make' => $this->up($line['tyre_make']),
                    'pattern' => $this->up($line['pattern']),
                    'mfg_week_year' => $line['mfg_week_year'] ?: null,
                    'pressure_psi' => $line['pressure_psi'] !== '' ? $line['pressure_psi'] : null,
                    'tread_depth_mm' => $line['tread_depth_mm'] !== '' ? $line['tread_depth_mm'] : null,
                    'condition' => $line['condition'],
                    'has_crack' => (bool) ($line['has_crack'] ?? false),
                    'has_bulge' => (bool) ($line['has_bulge'] ?? false),
                    'is_worn_out' => (bool) ($line['is_worn_out'] ?? false),
                    'has_puncture' => (bool) ($line['has_puncture'] ?? false),
                    'notes' => $this->up($line['notes']),
                    'sequence_no' => $i + 1,
                ],
            );
        }
    }

    private function up(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value ? strtoupper($value) : null;
    }

    public function render()
    {
        return view('tyre-report::edit');
    }
}
