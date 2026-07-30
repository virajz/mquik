<?php

namespace App\Modules\SurveyorInspection\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\SurveyorInspection\Models\SurveyorInspection;
use App\Modules\SurveyorInspection\Models\SurveyorInspectionAttachment;
use App\Modules\SurveyorInspection\Models\SurveyorInspectionItem;
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
#[Title('Surveyor Inspection')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $inspection_no = null;

    public ?int $job_card_id = null;

    public ?int $claim_intimation_id = null;

    public ?int $sales_estimate_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $insurance_company_id = null;

    public ?string $surveyor_name = null;

    public ?string $surveyor_phone = null;

    public ?string $survey_type = null;

    public ?string $surveyor_approval = null;

    public ?string $not_covered_reason = null;

    public ?string $rejection_reason = null;

    public string $status = SurveyorInspection::STATUS_PENDING;

    public ?string $surveyed_at = null;

    public ?string $surveyed_at_time = null;

    public ?string $notes = null;

    public string $vehicleSearch = '';

    public string $jobCardSearch = '';

    public string $estimateSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, array{id:?int, attachment_type:?string, kind:string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?SurveyorInspection $surveyorInspection = null): void
    {
        if ($surveyorInspection && $surveyorInspection->exists) {
            $this->load($surveyorInspection);
        }
    }

    protected function load(SurveyorInspection $s): void
    {
        $s->load(['items', 'attachments']);
        $this->editingId = $s->id;
        foreach ([
            'inspection_no', 'job_card_id', 'claim_intimation_id', 'sales_estimate_id', 'customer_id',
            'customer_vehicle_id', 'insurance_company_id', 'surveyor_name', 'surveyor_phone',
            'survey_type', 'surveyor_approval', 'not_covered_reason', 'rejection_reason', 'status', 'notes',
        ] as $k) {
            $this->{$k} = $s->{$k};
        }
        $this->surveyed_at = $s->surveyed_at?->format('Y-m-d');
        $this->surveyed_at_time = $s->surveyed_at?->format('H:i');

        $this->items = $s->items->map(fn (SurveyorInspectionItem $i) => [
            'id' => $i->id,
            'line_type' => $i->line_type,
            'spare_id' => $i->spare_id,
            'labour_id' => $i->labour_id,
            'description' => $i->description,
            'quantity' => $i->quantity,
            'line_approval' => $i->line_approval,
        ])->all();

        $this->attachments = $s->attachments->map(fn ($a) => [
            'id' => $a->id,
            'attachment_type' => $a->attachment_type,
            'kind' => $a->kind,
            'path' => $a->path,
            'original_name' => $a->original_name,
            'notes' => $a->notes,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'claim_intimation_id' => ['nullable', 'integer', Rule::exists('claim_intimations', 'id')],
            'sales_estimate_id' => ['nullable', 'integer', Rule::exists('sales_estimates', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'insurance_company_id' => ['nullable', 'integer', Rule::exists('insurance_companies', 'id')],
            'surveyor_name' => ['nullable', 'string', 'max:255'],
            'surveyor_phone' => ['nullable', 'string', 'max:20'],
            'survey_type' => ['nullable', Rule::in(array_keys(SurveyorInspection::surveyTypes()))],
            'surveyor_approval' => ['nullable', Rule::in(array_keys(SurveyorInspection::approvalOutcomes()))],
            'not_covered_reason' => ['nullable', Rule::in(array_keys(SurveyorInspection::notCoveredReasons()))],
            'rejection_reason' => ['nullable', Rule::in(array_keys(SurveyorInspection::rejectionReasons()))],
            'status' => ['required', Rule::in(array_keys(SurveyorInspection::statuses()))],
            'surveyed_at' => ['nullable', 'date', Rule::requiredIf(fn () => $this->status === SurveyorInspection::STATUS_COMPLETED)],
            'surveyed_at_time' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array'],
            'items.*.line_type' => ['required', 'in:spare,labour'],
            'items.*.spare_id' => ['nullable', 'integer', 'exists:spares,id', 'required_if:items.*.line_type,spare'],
            'items.*.labour_id' => ['nullable', 'integer', 'exists:labours,id', 'required_if:items.*.line_type,labour'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.line_approval' => ['nullable', Rule::in(array_keys(SurveyorInspectionItem::lineApprovals()))],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(SurveyorInspectionAttachment::attachmentTypes()))],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ];
    }

    public function addItem(string $type): void
    {
        $this->items[] = [
            'id' => null, 'line_type' => $type, 'spare_id' => null, 'labour_id' => null,
            'description' => '', 'quantity' => 1, 'line_approval' => 'pending',
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function addAttachment(): void
    {
        $this->attachments[] = ['id' => null, 'attachment_type' => 'approval_note', 'kind' => 'image', 'path' => null, 'original_name' => null, 'notes' => null];
    }

    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index], $this->attachmentFiles[$index]);
        $this->attachments = array_values($this->attachments);
    }

    #[Computed]
    public function companies()
    {
        return InsuranceCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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

    #[Computed]
    public function estimates()
    {
        return $this->pickerOptions(
            query: SalesEstimate::query()->latest('id'),
            searchColumns: ['estimate_no'],
            term: $this->estimateSearch,
            selected: $this->sales_estimate_id,
            columns: ['id', 'estimate_no'],
            limit: 30,
        );
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'surveyor_inspection.update' : 'surveyor_inspection.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['items'], $data['attachments'], $data['attachmentFiles']);

        foreach (['surveyed_at'] as $dtField) {
            if (! empty($data[$dtField])) {
                $data[$dtField] = trim($data[$dtField].' '.($this->{$dtField.'_time'} ?: '00:00'));
            }
            unset($data[$dtField.'_time']);
        }

        foreach (['surveyor_name', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $inspection = DB::transaction(function () use ($data, $items, $attachments, $isCreate) {
            if ($isCreate) {
                $row = SurveyorInspection::create($data);
                $this->editingId = $row->id;
                $this->inspection_no = $row->fresh()->inspection_no;
            } else {
                $row = SurveyorInspection::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Surveyor inspection '.$inspection->fresh()->inspection_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('surveyor-inspection.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncItems(SurveyorInspection $inspection, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = $inspection->items()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'line_type' => $row['line_type'],
                    'spare_id' => $row['line_type'] === 'spare' ? ($row['spare_id'] ?? null) : null,
                    'labour_id' => $row['line_type'] === 'labour' ? ($row['labour_id'] ?? null) : null,
                    'description' => strtoupper(trim((string) $row['description'])),
                    'quantity' => $row['quantity'],
                    'line_approval' => $row['line_approval'] ?: null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $inspection->items()->whereKeyNot($keptIds)->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncAttachments(SurveyorInspection $inspection, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('surveyor-inspections/'.$inspection->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $inspection->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'attachment_type' => $row['attachment_type'] ?: null,
                    'kind' => $kind,
                    'path' => $path,
                    'original_name' => $originalName,
                    'size_bytes' => $size,
                    'notes' => $row['notes'] ?: null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $inspection->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('surveyor-inspection::edit');
    }
}
