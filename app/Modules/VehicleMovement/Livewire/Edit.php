<?php

namespace App\Modules\VehicleMovement\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GatePassApproval\Models\GatePassApproval;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\VehicleMovement\Models\VehicleMovement;
use App\Modules\VehicleMovement\Models\VehicleMovementAttachment;
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
#[Title('Vehicle Inward / Outward')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $movement_no = null;

    public string $movement_type = VehicleMovement::TYPE_INWARD;

    public ?int $customer_vehicle_id = null;

    public ?int $job_card_id = null;

    public ?int $gate_pass_approval_id = null;

    public ?int $delivered_by_id = null;

    public ?int $security_guard_id = null;

    public ?string $parking_slot = null;

    public ?string $gate = null;

    public ?string $outward_type = null;

    public ?string $driver_type = null;

    public string $job_status = VehicleMovement::JOB_PENDING;

    public ?string $number_plate = null;

    public ?string $entry_at = null;

    public ?string $exit_at = null;

    public ?string $notes = null;

    public string $vehicleSearch = '';

    public string $jobCardSearch = '';

    public string $gatePassSearch = '';

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?VehicleMovement $vehicleMovement = null): void
    {
        if ($vehicleMovement && $vehicleMovement->exists) {
            $this->load($vehicleMovement);

            return;
        }

        $this->entry_at = now()->format('Y-m-d\TH:i');
    }

    protected function load(VehicleMovement $m): void
    {
        $m->load('attachments');
        $this->editingId = $m->id;
        foreach ([
            'movement_no', 'movement_type', 'customer_vehicle_id', 'job_card_id', 'gate_pass_approval_id',
            'delivered_by_id', 'security_guard_id', 'parking_slot', 'gate', 'outward_type', 'driver_type',
            'job_status', 'number_plate', 'notes',
        ] as $k) {
            $this->{$k} = $m->{$k};
        }
        $this->entry_at = $m->entry_at?->format('Y-m-d\TH:i');
        $this->exit_at = $m->exit_at?->format('Y-m-d\TH:i');

        $this->attachments = $m->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'movement_type' => ['required', Rule::in(array_keys(VehicleMovement::movementTypes()))],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'gate_pass_approval_id' => ['nullable', 'integer', Rule::exists('gate_pass_approvals', 'id')],
            'delivered_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'security_guard_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'parking_slot' => ['nullable', Rule::in(array_keys(VehicleMovement::parkingSlots()))],
            'gate' => ['nullable', Rule::in(array_keys(VehicleMovement::gates()))],
            'outward_type' => ['nullable', Rule::in(array_keys(VehicleMovement::outwardTypes())), Rule::requiredIf(fn () => $this->movement_type === VehicleMovement::TYPE_OUTWARD)],
            'driver_type' => ['nullable', Rule::in(array_keys(VehicleMovement::driverTypes()))],
            'job_status' => ['required', Rule::in(array_keys(VehicleMovement::jobStatuses()))],
            'number_plate' => ['nullable', 'string', 'max:20'],
            'entry_at' => ['nullable', 'date'],
            'exit_at' => ['nullable', 'date', 'after_or_equal:entry_at'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(VehicleMovementAttachment::attachmentTypes()))],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ];
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

    /** Prefill the number plate from the picked vehicle's registration. */
    public function updatedCustomerVehicleId($value): void
    {
        if (! $value) {
            return;
        }

        $vehicle = CustomerVehicleMaster::find($value);
        if ($vehicle && blank($this->number_plate)) {
            $this->number_plate = $vehicle->registration_no;
        }
    }

    // ---- Pickers -----------------------------------------------------------

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function vehicles()
    {
        return $this->pickerOptions(
            query: CustomerVehicleMaster::query()->orderBy('registration_no'),
            searchColumns: ['registration_no'], term: $this->vehicleSearch, selected: $this->customer_vehicle_id, columns: ['id', 'registration_no'], limit: 30,
        );
    }

    #[Computed]
    public function jobCards()
    {
        return $this->pickerOptions(
            query: JobCard::query()->latest('id'),
            searchColumns: ['job_card_no'], term: $this->jobCardSearch, selected: $this->job_card_id, columns: ['id', 'job_card_no'], limit: 30,
        );
    }

    #[Computed]
    public function gatePasses()
    {
        return $this->pickerOptions(
            query: GatePassApproval::query()->latest('id'),
            searchColumns: ['approval_no'], term: $this->gatePassSearch, selected: $this->gate_pass_approval_id, columns: ['id', 'approval_no'], limit: 30,
        );
    }

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'vehicle_movement.update' : 'vehicle_movement.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        if (isset($data['number_plate']) && is_string($data['number_plate'])) {
            $data['number_plate'] = strtoupper($data['number_plate']);
        }
        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }
        if ($data['movement_type'] !== VehicleMovement::TYPE_OUTWARD) {
            $data['outward_type'] = null;
        }

        $isCreate = $this->editingId === null;

        $movement = DB::transaction(function () use ($data, $attachments, $isCreate) {
            if ($isCreate) {
                $row = VehicleMovement::create($data);
                $this->editingId = $row->id;
                $this->movement_no = $row->fresh()->movement_no;
            } else {
                $row = VehicleMovement::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Movement '.$movement->fresh()->movement_no.($isCreate ? ' recorded.' : ' updated.'), variant: 'success');

        return redirect()->route('vehicle-movement.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(VehicleMovement $movement, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('vehicle-movements/'.$movement->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $movement->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $movement->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('vehicle-movement::edit');
    }
}
