<?php

namespace App\Modules\DocumentCollection\Livewire;

use App\Concerns\CanQuickAddCustomer;
use App\Concerns\CanQuickAddCustomerVehicle;
use App\Concerns\SearchesPickerOptions;
use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use App\Modules\ClaimTypeMaster\Models\ClaimTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DocumentCollection\Models\DocumentCollection;
use App\Modules\DocumentCollection\Models\DocumentCollectionItem;
use App\Modules\DocumentRejectionReasonMaster\Models\DocumentRejectionReasonMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\InsurancePolicyTypeMaster\Models\InsurancePolicyTypeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\MissingDocumentReasonMaster\Models\MissingDocumentReasonMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\ChildRows;
use Flux\Flux;
use Illuminate\Support\Carbon;
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
#[Title('Document Collection')]
class Edit extends Component
{
    use CanQuickAddCustomer;
    use CanQuickAddCustomerVehicle;
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $doc_collection_no = null;

    public ?int $customer_id = null;

    /** Search term for the server-backed customer picker (~9.7k rows). */
    public string $customerSearch = '';

    /** Job-first entry: most collections start from a job card, not a customer. */
    public string $jobSearch = '';

    public ?int $customer_vehicle_id = null;

    public ?int $job_card_id = null;

    public ?int $department_id = null;

    public ?int $service_type_id = null;

    public ?int $created_by_advisor_id = null;

    public ?int $collected_by_driver_id = null;

    public string $request_type = DocumentCollection::REQUEST_CUSTOMER;

    public ?string $purpose = null;

    public string $status = DocumentCollection::STATUS_PENDING;

    public ?int $insurance_company_id = null;

    public ?int $insurance_policy_type_id = null;

    public ?int $claim_type_id = null;

    public ?string $policy_no = null;

    public ?int $checklist_template_id = null;

    public ?int $verification_template_id = null;

    public ?int $missing_document_reason_id = null;

    public ?int $rejection_reason_id = null;

    public ?int $follow_up_mode_id = null;

    /** @var list<array{id:?int, followed_up_at:string, followed_up_by_id:?int, follow_up_mode_id:?int, customer_response:?string}> */
    public array $followUps = [];

    public ?string $reminder_frequency = null;

    public ?int $reminder_custom_days = null;

    public string $retention = 'active';

    public ?int $retention_days = null;

    public string $requested_date = '';

    public string $requested_time = '';

    public string $received_date = '';

    public string $received_time = '';

    public ?string $notes = null;

    /** @var list<array{id: ?int, label: string, is_required: bool, status: string, rejection_reason_id: ?int, notes: ?string, path: ?string, original_name: ?string}> */
    public array $items = [];

    /** @var array<int, TemporaryUploadedFile> staged item files, keyed by item index */
    public array $itemFiles = [];

    /** @var list<array{id: ?int, label: string, is_verified: bool, notes: ?string}> */
    public array $verifications = [];

    public ?TemporaryUploadedFile $signatureUpload = null;

    public bool $clearSignature = false;

    /** ?int — passed via ?from-job-card=ID for the JobCard → Document Collection handoff. */
    #[Url(as: 'from-job-card')]
    public ?int $fromJobCard = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    public function mount(?DocumentCollection $documentCollection = null): void
    {
        if ($documentCollection && $documentCollection->exists) {
            $this->load($documentCollection);
            // Legacy rows can hold a service type their department no longer offers.
            $this->guardDepartmentPairing();

            return;
        }

        $now = now();
        $this->requested_date = $now->format('Y-m-d');
        $this->requested_time = $now->format('H:i');

        if ($this->fromJobCard) {
            $this->prefillFromJobCard($this->fromJobCard);
        }

        $this->guardDepartmentPairing();
    }

    /** The card already knows whose documents these are — inherit rather than re-ask. */
    protected function prefillFromJobCard(int $jobCardId): void
    {
        $jobCard = JobCard::find($jobCardId);

        if (! $jobCard) {
            return;
        }

        $this->job_card_id = $jobCard->id;
        $this->customer_id = $jobCard->customer_id;
        $this->customer_vehicle_id = $jobCard->customer_vehicle_id;
        $this->department_id = $jobCard->workshop_department_id ?? $this->department_id;
        $this->service_type_id = $jobCard->service_type_id ?? $this->service_type_id;
        $this->created_by_advisor_id = $jobCard->assigned_advisor_id ?? $this->created_by_advisor_id;
        $this->insurance_company_id = $jobCard->insurance_company_id ?? $this->insurance_company_id;

        unset($this->serviceTypes);
    }

    protected function load(DocumentCollection $dc): void
    {
        $dc->load(['items', 'verifications']);

        $this->editingId = $dc->id;
        $this->doc_collection_no = $dc->doc_collection_no;
        $this->customer_id = $dc->customer_id;
        $this->customer_vehicle_id = $dc->customer_vehicle_id;
        $this->job_card_id = $dc->job_card_id;
        $this->department_id = $dc->department_id;
        $this->service_type_id = $dc->service_type_id;
        $this->created_by_advisor_id = $dc->created_by_advisor_id;
        $this->collected_by_driver_id = $dc->collected_by_driver_id;
        $this->request_type = $dc->request_type;
        $this->purpose = $dc->purpose;
        $this->status = $dc->status;
        $this->insurance_company_id = $dc->insurance_company_id;
        $this->insurance_policy_type_id = $dc->insurance_policy_type_id;
        $this->claim_type_id = $dc->claim_type_id;
        $this->policy_no = $dc->policy_no;
        $this->checklist_template_id = $dc->checklist_template_id;
        $this->verification_template_id = $dc->verification_template_id;
        $this->missing_document_reason_id = $dc->missing_document_reason_id;
        $this->rejection_reason_id = $dc->rejection_reason_id;
        $this->follow_up_mode_id = $dc->follow_up_mode_id;
        $this->reminder_frequency = $dc->reminder_frequency;
        $this->reminder_custom_days = $dc->reminder_custom_days;
        $this->retention = $dc->retention;
        $this->retention_days = $dc->retention_days;
        $this->followUps = $dc->followUps()->get()->map(fn ($f) => [
            'id' => $f->id,
            'followed_up_at' => $f->followed_up_at?->format('Y-m-d\TH:i'),
            'followed_up_by_id' => $f->followed_up_by_id,
            'follow_up_mode_id' => $f->follow_up_mode_id,
            'customer_response' => $f->customer_response,
        ])->all();

        $this->requested_date = $dc->requested_at?->format('Y-m-d') ?? '';
        $this->requested_time = $dc->requested_at?->format('H:i') ?? '';
        $this->received_date = $dc->received_at?->format('Y-m-d') ?? '';
        $this->received_time = $dc->received_at?->format('H:i') ?? '';
        $this->notes = $dc->notes;

        $this->items = $dc->items->map(fn ($i) => [
            'id' => $i->id,
            'label' => $i->label,
            'is_required' => (bool) $i->is_required,
            'status' => $i->status,
            'received_at' => $i->received_at?->toDateTimeString(),
            'rejection_reason_id' => $i->rejection_reason_id,
            'notes' => $i->notes,
            'path' => $i->path,
            'original_name' => $i->original_name,
        ])->all();

        $this->verifications = $dc->verifications->map(fn ($v) => [
            'id' => $v->id,
            'label' => $v->label,
            'is_verified' => (bool) $v->is_verified,
            'notes' => $v->notes,
        ])->all();
    }

    public function updatedCustomerId(): void
    {
        $this->customer_vehicle_id = null;
        $this->job_card_id = null;
    }

    /** When the document checklist template changes, snapshot its items in. */
    public function updatedChecklistTemplateId(): void
    {
        $this->applyChecklistTemplate();
    }

    public function applyChecklistTemplate(): void
    {
        if (! $this->checklist_template_id) {
            return;
        }

        $tpl = ChecklistTemplateMaster::find($this->checklist_template_id);
        if (! $tpl) {
            return;
        }

        // Only seed when empty, to avoid clobbering captured rows.
        if (count($this->items) > 0) {
            return;
        }

        $this->items = collect($tpl->items ?? [])->map(fn ($it) => [
            'id' => null,
            'label' => strtoupper((string) ($it['label'] ?? '')),
            'is_required' => (bool) ($it['is_required'] ?? false),
            'status' => DocumentCollectionItem::STATUS_PENDING,
            'rejection_reason_id' => null,
            'notes' => null,
            'path' => null,
            'original_name' => null,
        ])->values()->all();
    }

    public function updatedVerificationTemplateId(): void
    {
        if (! $this->verification_template_id || count($this->verifications) > 0) {
            return;
        }

        $tpl = ChecklistTemplateMaster::find($this->verification_template_id);
        if (! $tpl) {
            return;
        }

        $this->verifications = collect($tpl->items ?? [])->map(fn ($it) => [
            'id' => null,
            'label' => strtoupper((string) ($it['label'] ?? '')),
            'is_verified' => false,
            'notes' => null,
        ])->values()->all();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'id' => null, 'label' => '', 'is_required' => false,
            'status' => DocumentCollectionItem::STATUS_PENDING,
            'rejection_reason_id' => null, 'notes' => null, 'path' => null, 'original_name' => null,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index], $this->itemFiles[$index]);
        $this->items = array_values($this->items);
        $this->itemFiles = array_values($this->itemFiles);
    }

    public function addVerification(): void
    {
        $this->verifications[] = ['id' => null, 'label' => '', 'is_verified' => false, 'notes' => null];
    }

    public function removeVerification(int $index): void
    {
        unset($this->verifications[$index]);
        $this->verifications = array_values($this->verifications);
    }

    public function markClearSignature(): void
    {
        $this->clearSignature = true;
        $this->signatureUpload = null;
    }

    protected function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'customer_vehicle_id' => ['required', 'integer', 'exists:customer_vehicles,id'],
            'job_card_id' => ['nullable', 'integer', 'exists:job_cards,id'],
            'department_id' => ['required', 'integer', 'exists:workshop_departments,id'],
            'service_type_id' => [
                'required', 'integer',
                Rule::exists('service_types', 'id')->where('workshop_department_id', $this->department_id),
            ],
            'created_by_advisor_id' => ['required', 'integer', 'exists:employees,id'],
            'collected_by_driver_id' => ['nullable', 'integer', 'exists:employees,id'],
            'request_type' => ['required', Rule::in(array_keys(DocumentCollection::requestTypes()))],
            'purpose' => ['required', Rule::in(array_keys(DocumentCollection::purposes()))],

            'insurance_company_id' => ['nullable', 'integer', 'exists:insurance_companies,id'],
            'insurance_policy_type_id' => ['nullable', 'integer', 'exists:insurance_policy_types,id'],
            'claim_type_id' => ['nullable', 'integer', 'exists:claim_types,id'],
            'policy_no' => ['nullable', 'string', 'max:60'],
            'checklist_template_id' => ['required', 'integer', 'exists:checklist_templates,id'],
            'verification_template_id' => ['required', 'integer', 'exists:checklist_templates,id'],
            'missing_document_reason_id' => ['nullable', 'integer', 'exists:missing_document_reasons,id'],
            'rejection_reason_id' => ['nullable', 'integer', 'exists:document_rejection_reasons,id'],
            'follow_up_mode_id' => ['nullable', 'integer', 'exists:follow_up_modes,id'],
            'followUps' => ['array'],
            'followUps.*.followed_up_at' => ['required', 'date'],
            'followUps.*.followed_up_by_id' => ['nullable', 'integer', 'exists:employees,id'],
            'followUps.*.follow_up_mode_id' => ['nullable', 'integer', 'exists:follow_up_modes,id'],
            'followUps.*.customer_response' => ['nullable', 'string', 'max:500'],
            'reminder_frequency' => ['nullable', Rule::in(array_keys(DocumentCollection::reminderFrequencies()))],
            'reminder_custom_days' => [
                Rule::requiredIf(fn () => $this->reminder_frequency === DocumentCollection::REMINDER_CUSTOM),
                'nullable', 'integer', 'min:1', 'max:365',
            ],
            'retention' => ['required', Rule::in(array_keys(DocumentCollection::retentions()))],
            'retention_days' => [
                Rule::requiredIf(fn () => $this->retention === DocumentCollection::RETENTION_DELETE),
                'nullable', 'integer', 'min:1', 'max:3650',
            ],
            'requested_date' => ['nullable', 'date_format:Y-m-d'],
            'requested_time' => ['nullable', 'date_format:H:i'],
            'received_date' => ['nullable', 'date_format:Y-m-d'],
            'received_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.status' => ['required', Rule::in(array_keys(DocumentCollectionItem::statuses()))],
            'items.*.rejection_reason_id' => ['nullable', 'integer', 'exists:document_rejection_reasons,id'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],

            'verifications' => ['array'],
            'verifications.*.label' => ['required', 'string', 'max:255'],
            'verifications.*.notes' => ['nullable', 'string', 'max:500'],

            'itemFiles' => ['array'],
            'itemFiles.*' => ['file', 'mimes:jpg,jpeg,png,pdf,docx', 'max:8192'],
            'signatureUpload' => ['nullable', 'image', 'max:2048'],
        ];
    }

    #[Computed]
    public function customers()
    {
        // Server-side search: the customer master is ~9.7k rows, so a fixed
        // client-side slice would hide everyone past the first page. The
        // selected customer is always retained so an edit form keeps its label.
        return $this->pickerOptions(
            query: CustomerMaster::query()
                ->where('is_active', true)
                ->orderBy('first_name'),
            searchColumns: ['first_name', 'last_name', 'phone'],
            term: $this->customerSearch,
            selected: $this->customer_id,
            columns: ['id', 'first_name', 'last_name', 'phone'],
            limit: 30,
        );
    }

    #[Computed]
    public function customerVehicles()
    {
        if (! $this->customer_id) {
            return collect();
        }

        return CustomerVehicleMaster::query()
            ->with(['model.brand'])
            ->where('customer_id', $this->customer_id)
            ->where('is_active', true)
            ->orderBy('registration_no')
            ->get(['id', 'registration_no', 'model_id'])
            ->map(fn ($v) => [
                'id' => $v->id,
                'label' => trim(($v->model?->brand?->name ?? '').' '.($v->model?->name ?? '')).' — '.$v->registration_no,
            ]);
    }

    /**
     * Job-first: the picker searches every job card by number, reg or customer,
     * labelled "JC-92100 — MARUTI SWIFT — GJ 05 RD 1234" so the advisor can
     * confirm they have the right car before anything auto-fills.
     */
    #[Computed]
    public function jobCardOptions()
    {
        $term = trim($this->jobSearch);

        return JobCard::query()
            ->with(['customerVehicle:id,registration_no,model_id', 'customerVehicle.model:id,name,brand_id', 'customerVehicle.model.brand:id,name'])
            ->when($term !== '', fn ($q) => $q->search($term))
            ->when($term === '' && $this->customer_vehicle_id, fn ($q) => $q->where('customer_vehicle_id', $this->customer_vehicle_id))
            ->orderByDesc('opened_at')
            ->limit(25)
            ->get(['id', 'job_card_no', 'customer_vehicle_id'])
            ->map(fn ($jc) => [
                'id' => $jc->id,
                'label' => collect([
                    $jc->job_card_no,
                    trim(($jc->customerVehicle?->model?->brand?->name ?? '').' '.($jc->customerVehicle?->model?->name ?? '')) ?: null,
                    $jc->customerVehicle?->registration_no,
                ])->filter()->implode(' — '),
            ]);
    }

    /** Picking a job fills everything the job already knows. */
    public function updatedJobCardId(): void
    {
        if (! $this->job_card_id) {
            return;
        }

        $jc = JobCard::find($this->job_card_id);

        if (! $jc) {
            return;
        }

        $this->customer_id = $jc->customer_id;
        $this->customer_vehicle_id = $jc->customer_vehicle_id;
        $this->department_id = $jc->workshop_department_id ?? $this->department_id;
        $this->service_type_id = $jc->service_type_id ?? $this->service_type_id;
        $this->created_by_advisor_id = $this->created_by_advisor_id ?? $jc->assigned_advisor_id;

        unset($this->serviceTypes);
        $this->guardDepartmentPairing();
    }

    /** House convention: the department decides which service types exist. */
    public function updatedDepartmentId(): void
    {
        $this->service_type_id = null;

        unset($this->serviceTypes);
    }

    protected function guardDepartmentPairing(): void
    {
        if ($this->service_type_id && ! $this->serviceTypes->contains('id', $this->service_type_id)) {
            $this->service_type_id = null;
        }
    }

    /** Required by CanQuickAddCustomer — receives the new customer's id. */
    protected function quickCustomerTargetProperty(): string
    {
        return 'customer_id';
    }

    /** Required by CanQuickAddCustomerVehicle — receives the new vehicle's id. */
    protected function quickCustomerVehicleTargetProperty(): string
    {
        return 'customer_vehicle_id';
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function departments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function serviceTypes()
    {
        if (! $this->department_id) {
            return collect();
        }

        return ServiceTypeMaster::query()
            ->where('is_active', true)
            ->where('workshop_department_id', $this->department_id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function insuranceCompanies()
    {
        return InsuranceCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function policyTypes()
    {
        return InsurancePolicyTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function claimTypes()
    {
        return ClaimTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function checklistTemplates()
    {
        return ChecklistTemplateMaster::query()
            ->where('is_active', true)
            ->whereIn('applies_to', ['claim', 'generic'])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function missingReasons()
    {
        return MissingDocumentReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function rejectionReasons()
    {
        return DocumentRejectionReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function followUpModes()
    {
        return FollowUpModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'document_collection.update' : 'document_collection.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $verifications = $data['verifications'] ?? [];
        $followUps = $data['followUps'] ?? [];
        unset($data['items'], $data['verifications'], $data['followUps'], $data['itemFiles'], $data['signatureUpload']);

        // Requested is stamped when the record is created — asking for documents
        // IS the request. Received is per-document now; the header stamp derives
        // as "the moment the last required document landed".
        unset($data['requested_date'], $data['requested_time'], $data['received_date'], $data['received_time']);

        if ($this->editingId === null) {
            $data['requested_at'] = now();
        }

        foreach (['policy_no', 'notes'] as $k) {
            if (filled($data[$k] ?? null)) {
                $data[$k] = strtoupper((string) $data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $dc = DB::transaction(function () use ($data, $items, $verifications, $followUps, $isCreate) {
            if ($isCreate) {
                $data['entry_at'] = now();
                $row = DocumentCollection::create($data);
                $this->editingId = $row->id;
            } else {
                $row = DocumentCollection::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncVerifications($row, $verifications);
            $this->syncFollowUps($row, $followUps);
            $this->deriveStatus($row);
            $this->syncSignature($row);

            return $row;
        });

        $this->itemFiles = [];
        $this->signatureUpload = null;
        $this->clearSignature = false;

        Flux::toast(text: 'Document Collection '.$dc->fresh()->doc_collection_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        if ($isCreate) {
            return redirect()->route('document-collection.edit', $dc->id);
        }

        return redirect()->route('document-collection.index');
    }

    protected function combineDateTime(string $date, string $time): ?Carbon
    {
        if (empty($date) || empty($time)) {
            return null;
        }

        return Carbon::parse($date.' '.$time.':00');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    /**
     * Status reads off the documents, never off a dropdown:
     * cancelled and rejected stay deliberate; received means every required
     * document is in (stamping the header with the last arrival); requested is
     * the base state — the record existing IS the request.
     */
    public function addFollowUp(): void
    {
        $this->followUps[] = [
            'id' => null,
            'followed_up_at' => now()->format('Y-m-d\TH:i'),
            'followed_up_by_id' => $this->created_by_advisor_id,
            'follow_up_mode_id' => $this->follow_up_mode_id,
            'customer_response' => null,
        ];
    }

    public function removeFollowUp(int $index): void
    {
        unset($this->followUps[$index]);
        $this->followUps = array_values($this->followUps);
    }

    protected function syncFollowUps(DocumentCollection $dc, array $rows): void
    {
        $kept = [];

        foreach (array_values($rows) as $row) {
            $kept[] = ChildRows::upsert($dc->followUps(), $row['id'] ?? null, [
                'followed_up_at' => $row['followed_up_at'],
                'followed_up_by_id' => $row['followed_up_by_id'] ?: null,
                'follow_up_mode_id' => $row['follow_up_mode_id'] ?: null,
                'customer_response' => filled($row['customer_response'] ?? null) ? strtoupper((string) $row['customer_response']) : null,
            ])->id;
        }

        $dc->followUps()->whereKeyNot($kept)->delete();
    }

    /**
     * The message an advisor sends the customer: which documents are still
     * outstanding, for which vehicle. Plain text so it pastes anywhere;
     * the WhatsApp link is just this, URL-encoded.
     */
    #[Computed]
    public function requestMessage(): string
    {
        $vehicle = $this->customer_vehicle_id
            ? CustomerVehicleMaster::with('model.brand')->find($this->customer_vehicle_id)
            : null;

        $pendingDocs = collect($this->items)
            ->filter(fn ($i) => ($i['status'] ?? 'pending') !== DocumentCollectionItem::STATUS_RECEIVED)
            ->pluck('label')
            ->filter()
            ->values();

        $lines = collect([
            'Dear Customer,',
            'We need the following documents for your vehicle'
                .($vehicle ? ' '.trim(($vehicle->model?->brand?->name ?? '').' '.($vehicle->model?->name ?? '')).' ('.$vehicle->registration_no.')' : '').':',
        ])->merge($pendingDocs->map(fn ($d, $i) => ($i + 1).'. '.$d))
            ->push('Please share them at the earliest. Thank you — '.config('app.name'));

        return $lines->implode("\n");
    }

    /** wa.me link with the message pre-filled, when the customer has a phone. */
    #[Computed]
    public function whatsAppUrl(): ?string
    {
        $phone = $this->customer_id ? CustomerMaster::whereKey($this->customer_id)->value('phone') : null;

        return $phone
            ? 'https://wa.me/91'.preg_replace('/\D/', '', $phone).'?text='.rawurlencode($this->requestMessage)
            : null;
    }

    protected function deriveStatus(DocumentCollection $dc): void
    {
        if (in_array($dc->status, [DocumentCollection::STATUS_CANCELLED, DocumentCollection::STATUS_REJECTED], true)) {
            return;
        }

        $items = $dc->items()->get();
        $required = $items->where('is_required', true);
        $pool = $required->isNotEmpty() ? $required : $items;

        $allIn = $pool->isNotEmpty() && $pool->every(
            fn ($i) => $i->status === DocumentCollectionItem::STATUS_RECEIVED,
        );

        $dc->forceFill([
            'status' => $allIn ? DocumentCollection::STATUS_RECEIVED : DocumentCollection::STATUS_REQUESTED,
            'received_at' => $allIn ? $pool->max('received_at') : null,
        ])->saveQuietly();

        $this->status = $dc->status;
    }

    protected function syncItems(DocumentCollection $dc, array $rows): void
    {
        $keptIds = [];
        $uploadedAny = false;

        foreach ($rows as $i => $row) {
            $payload = [
                'label' => strtoupper((string) $row['label']),
                'is_required' => (bool) ($this->items[$i]['is_required'] ?? false),
                'status' => $row['status'],
                'rejection_reason_id' => $row['status'] === DocumentCollectionItem::STATUS_REJECTED ? ($row['rejection_reason_id'] ?? null) : null,
                'notes' => filled($row['notes'] ?? null) ? strtoupper((string) $row['notes']) : null,
                'sequence_no' => $i + 1,
            ];

            $existingId = $this->items[$i]['id'] ?? null;
            $item = ($existingId ? $dc->items()->whereKey($existingId)->first() : null) ?? $dc->items()->make();

            // Each document stamps its own arrival the moment it flips to
            // received — the RC book on Monday, the policy on Thursday.
            $payload['received_at'] = match (true) {
                $row['status'] !== DocumentCollectionItem::STATUS_RECEIVED => null,
                $item->exists && $item->status === DocumentCollectionItem::STATUS_RECEIVED => $item->received_at,
                default => now(),
            };

            $item->fill($payload);

            // Attach a newly-staged file for this row.
            if (isset($this->itemFiles[$i]) && $this->itemFiles[$i] instanceof TemporaryUploadedFile) {
                $file = $this->itemFiles[$i];
                if ($item->path) {
                    Storage::disk('public')->delete($item->path);
                }
                $item->path = $file->store("document-collections/{$dc->id}/items", 'public');
                $item->original_name = $file->getClientOriginalName();
                $item->mime_type = $file->getMimeType();
                $item->size_bytes = $file->getSize();
                $uploadedAny = true;
            }

            $item->document_collection_id = $dc->id;
            $item->save();
            $keptIds[] = $item->id;
        }

        // Delete removed rows (and their files).
        $dc->items()->whereNotIn('id', $keptIds)->get()->each(function ($old) {
            if ($old->path) {
                Storage::disk('public')->delete($old->path);
            }
            $old->delete();
        });

        if ($uploadedAny && ! $dc->uploaded_at) {
            $dc->forceFill(['uploaded_at' => now()])->save();
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncVerifications(DocumentCollection $dc, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $i => $vrow) {
            $payload = [
                'label' => strtoupper((string) $vrow['label']),
                'is_verified' => (bool) ($this->verifications[$i]['is_verified'] ?? false),
                'notes' => filled($vrow['notes'] ?? null) ? strtoupper((string) $vrow['notes']) : null,
                'sequence_no' => $i + 1,
            ];

            $existingId = $this->verifications[$i]['id'] ?? null;
            $model = ($existingId ? $dc->verifications()->whereKey($existingId)->first() : null) ?? $dc->verifications()->make();
            $model->fill($payload);
            $model->document_collection_id = $dc->id;
            $model->save();
            $keptIds[] = $model->id;
        }

        $dc->verifications()->whereNotIn('id', $keptIds)->delete();
    }

    protected function syncSignature(DocumentCollection $dc): void
    {
        if ($this->clearSignature && $dc->customer_signature_path) {
            Storage::disk('public')->delete($dc->customer_signature_path);
            $dc->forceFill(['customer_signature_path' => null])->save();
        }

        if ($this->signatureUpload instanceof TemporaryUploadedFile) {
            if ($dc->customer_signature_path) {
                Storage::disk('public')->delete($dc->customer_signature_path);
            }
            $path = $this->signatureUpload->store("document-collections/{$dc->id}/signature", 'public');
            $dc->forceFill(['customer_signature_path' => $path])->save();
        }
    }

    public function render()
    {
        return view('document-collection::edit');
    }
}
