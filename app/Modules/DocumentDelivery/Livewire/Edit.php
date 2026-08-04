<?php

namespace App\Modules\DocumentDelivery\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DocumentDelivery\Models\DocumentDelivery;
use App\Modules\DocumentDelivery\Models\DocumentDeliveryItem;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\MissingDocumentReasonMaster\Models\MissingDocumentReasonMaster;
use App\Support\ChildRows;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Document Delivery')]
class Edit extends Component
{
    use SearchesPickerOptions;

    public ?int $editingId = null;

    public ?string $delivery_no = null;

    public ?int $job_card_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $insurance_company_id = null;

    public ?string $delivery_state = null;

    public ?string $delivery_city = null;

    public ?string $delivery_area = null;

    public ?int $advisor_employee_id = null;

    public ?int $driver_employee_id = null;

    public ?int $courier_company_id = null;

    public ?int $missing_document_reason_id = null;

    public ?int $follow_up_mode_id = null;

    public ?string $recipient_type = null;

    public ?string $delivery_mode = null;

    public ?string $acknowledgement_type = null;

    public string $status = DocumentDelivery::STATUS_PENDING;

    public ?string $delivery_failure_reason = null;

    public ?string $reminder_frequency = null;

    public ?int $reminder_custom_days = null;

    public ?string $delivered_at = null;

    public ?string $notes = null;

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    public string $jobCardSearch = '';

    /** @var array<int, array{id:?int, document_name:string, is_delivered:bool, notes:?string}> */
    public array $items = [];

    public function mount(?DocumentDelivery $documentDelivery = null): void
    {
        if ($documentDelivery && $documentDelivery->exists) {
            $this->load($documentDelivery);

            return;
        }

        // Seed the standard document checklist.
        $this->items = array_map(fn ($name) => [
            'id' => null, 'document_name' => $name, 'is_delivered' => false, 'notes' => null,
        ], DocumentDelivery::standardDocuments());
    }

    protected function load(DocumentDelivery $d): void
    {
        $d->load('items');
        $this->editingId = $d->id;
        foreach ([
            'delivery_no', 'job_card_id', 'customer_id', 'customer_vehicle_id', 'insurance_company_id',
            'delivery_state', 'delivery_city', 'delivery_area', 'advisor_employee_id', 'driver_employee_id',
            'courier_company_id', 'missing_document_reason_id', 'follow_up_mode_id', 'recipient_type',
            'delivery_mode', 'acknowledgement_type', 'status', 'delivery_failure_reason',
            'reminder_frequency', 'reminder_custom_days', 'notes',
        ] as $k) {
            $this->{$k} = $d->{$k};
        }
        $this->delivered_at = $d->delivered_at?->format('Y-m-d');

        $this->items = $d->items->map(fn (DocumentDeliveryItem $i) => [
            'id' => $i->id,
            'document_name' => $i->document_name,
            'is_delivered' => (bool) $i->is_delivered,
            'notes' => $i->notes,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'insurance_company_id' => ['nullable', 'integer', Rule::exists('insurance_companies', 'id')],
            'delivery_state' => ['nullable', 'string', 'max:100'],
            'delivery_city' => ['nullable', 'string', 'max:100'],
            'delivery_area' => ['nullable', 'string', 'max:255'],
            'advisor_employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'driver_employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'courier_company_id' => ['nullable', 'integer', Rule::exists('courier_companies', 'id')],
            'missing_document_reason_id' => ['nullable', 'integer', Rule::exists('missing_document_reasons', 'id')],
            'follow_up_mode_id' => ['nullable', 'integer', Rule::exists('follow_up_modes', 'id')],
            'recipient_type' => ['nullable', Rule::in(array_keys(DocumentDelivery::recipientTypes()))],
            'delivery_mode' => ['nullable', Rule::in(array_keys(DocumentDelivery::deliveryModes()))],
            'acknowledgement_type' => ['nullable', Rule::in(array_keys(DocumentDelivery::acknowledgementTypes()))],
            'status' => ['required', Rule::in(array_keys(DocumentDelivery::statuses()))],
            'delivery_failure_reason' => ['nullable', Rule::in(array_keys(DocumentDelivery::failureReasons()))],
            'reminder_frequency' => ['nullable', Rule::in(array_keys(DocumentDelivery::reminderFrequencies()))],
            'reminder_custom_days' => ['nullable', 'integer', 'min:1', 'max:90', Rule::requiredIf(fn () => $this->reminder_frequency === 'custom')],
            'delivered_at' => ['nullable', 'date', Rule::requiredIf(fn () => $this->status === DocumentDelivery::STATUS_DELIVERED)],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array'],
            'items.*.document_name' => ['required', 'string', 'max:255'],
            'items.*.is_delivered' => ['boolean'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function addItem(): void
    {
        $this->items[] = ['id' => null, 'document_name' => '', 'is_delivered' => false, 'notes' => null];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    #[Computed]
    public function companies()
    {
        return InsuranceCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function couriers()
    {
        return CourierCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function missingReasons()
    {
        return MissingDocumentReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function followUpModes()
    {
        return FollowUpModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function customers()
    {
        return $this->pickerOptions(
            query: CustomerMaster::query()->where('is_active', true)->orderBy('first_name'),
            searchColumns: ['first_name', 'last_name', 'phone'],
            term: $this->customerSearch,
            selected: $this->customer_id,
            columns: ['id', 'first_name', 'last_name'],
            limit: 30,
        );
    }

    #[Computed]
    public function vehicles()
    {
        return $this->pickerOptions(
            query: CustomerVehicleMaster::query()->orderBy('registration_no'),
            searchColumns: ['registration_no'],
            term: $this->vehicleSearch,
            selected: $this->customer_vehicle_id,
            columns: ['id', 'registration_no'],
            limit: 30,
        );
    }

    #[Computed]
    public function jobCards()
    {
        return $this->pickerOptions(
            query: JobCard::query()->latest('id'),
            searchColumns: ['job_card_no'],
            term: $this->jobCardSearch,
            selected: $this->job_card_id,
            columns: ['id', 'job_card_no'],
            limit: 30,
        );
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'document_delivery.update' : 'document_delivery.create');

        // Strip blank checklist rows.
        $this->items = array_values(array_filter(
            $this->items,
            fn ($i) => filled($i['document_name'] ?? null),
        ));

        $data = $this->validate();
        $items = $data['items'] ?? [];
        unset($data['items']);

        foreach (['delivery_state', 'delivery_city', 'delivery_area', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }
        if ($data['reminder_frequency'] !== 'custom') {
            $data['reminder_custom_days'] = null;
        }

        $isCreate = $this->editingId === null;

        $delivery = DB::transaction(function () use ($data, $items, $isCreate) {
            if ($isCreate) {
                $row = DocumentDelivery::create($data);
                $this->editingId = $row->id;
                $this->delivery_no = $row->fresh()->delivery_no;
            } else {
                $row = DocumentDelivery::findOrFail($this->editingId);
                $row->update($data);
            }

            $keptIds = [];
            foreach (array_values($items) as $i => $doc) {
                $keptIds[] = ChildRows::upsert($row->items(), $doc['id'] ?? null,
                    [
                        'document_name' => strtoupper(trim((string) $doc['document_name'])),
                        'is_delivered' => (bool) ($doc['is_delivered'] ?? false),
                        'notes' => isset($doc['notes']) && is_string($doc['notes']) ? strtoupper($doc['notes']) : null,
                        'sequence_no' => $i + 1,
                    ],
                )->id;
            }
            $row->items()->whereKeyNot($keptIds)->delete();

            return $row;
        });

        Flux::toast(text: 'Document delivery '.$delivery->fresh()->delivery_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('document-delivery.index');
    }

    public function render()
    {
        return view('document-delivery::edit');
    }
}
