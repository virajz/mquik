<?php

namespace App\Modules\RegularReceipt\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\ChequeBounceReasonMaster\Models\ChequeBounceReasonMaster;
use App\Modules\CounterSalesInvoice\Models\CounterSalesInvoice;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use App\Modules\ReceiptCancellationReasonMaster\Models\ReceiptCancellationReasonMaster;
use App\Modules\ReceiptDifferenceReasonMaster\Models\ReceiptDifferenceReasonMaster;
use App\Modules\RegularReceipt\Models\RegularReceipt;
use App\Modules\RegularSalesInvoice\Models\RegularSalesInvoice;
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
#[Title('Regular Receipt')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $receipt_no = null;

    public string $status = 'draft';

    public ?int $customer_id = null;

    /** Search term for the server-backed customer picker (~9.7k rows). */
    public string $customerSearch = '';

    public ?int $insurance_company_id = null;

    public ?int $regular_sales_invoice_id = null;

    public ?int $counter_sales_invoice_id = null;

    public ?int $payment_mode_id = null;

    public ?int $bank_id = null;

    public ?int $received_by_id = null;

    public ?int $advance_receipt_id = null;

    public float $amount = 0;

    public float $difference_amount = 0;

    public ?int $receipt_difference_reason_id = null;

    public ?string $cheque_no = null;

    public ?string $cheque_date = null;

    public ?string $cheque_status = null;

    public ?int $cheque_bounce_reason_id = null;

    public ?string $reference_no = null;

    public ?int $cancellation_reason_id = null;

    public ?string $notes = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    #[Url(as: 'from-regular-invoice')]
    public ?int $fromRegularInvoice = null;

    #[Url(as: 'from-counter-invoice')]
    public ?int $fromCounterInvoice = null;

    public ?string $attachmentType = null;

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachmentFiles = [];

    /** @var list<int> */
    public array $removedAttachmentIds = [];

    public function mount(?RegularReceipt $regularReceipt = null): void
    {
        if ($regularReceipt && $regularReceipt->exists) {
            $this->load($regularReceipt);

            return;
        }

        if ($this->fromRegularInvoice) {
            $inv = RegularSalesInvoice::find($this->fromRegularInvoice);
            if ($inv) {
                $this->regular_sales_invoice_id = $inv->id;
                $this->customer_id = $inv->customer_id;
                $this->insurance_company_id = $inv->insurance_company_id;
                $this->amount = (float) $inv->balance_due;
            }

            return;
        }

        if ($this->fromCounterInvoice) {
            $inv = CounterSalesInvoice::find($this->fromCounterInvoice);
            if ($inv) {
                $this->counter_sales_invoice_id = $inv->id;
                $this->customer_id = $inv->customer_id;
                $this->amount = (float) $inv->balance_due;
            }
        }
    }

    protected function load(RegularReceipt $r): void
    {
        $this->editingId = $r->id;
        foreach ([
            'receipt_no', 'status', 'customer_id', 'insurance_company_id', 'regular_sales_invoice_id',
            'counter_sales_invoice_id', 'payment_mode_id', 'bank_id', 'received_by_id', 'advance_receipt_id',
            'receipt_difference_reason_id', 'cheque_no', 'cheque_status', 'cheque_bounce_reason_id',
            'reference_no', 'cancellation_reason_id', 'notes',
        ] as $k) {
            $this->{$k} = $r->{$k};
        }
        $this->amount = (float) $r->amount;
        $this->difference_amount = (float) $r->difference_amount;
        $this->cheque_date = $r->cheque_date?->format('Y-m-d');
    }

    public function removeAttachment(int $id): void
    {
        if (! in_array($id, $this->removedAttachmentIds, true)) {
            $this->removedAttachmentIds[] = $id;
        }
    }

    protected function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'status' => ['required', Rule::in(array_keys(RegularReceipt::statuses()))],
            'insurance_company_id' => ['nullable', 'integer', 'exists:insurance_companies,id'],
            'regular_sales_invoice_id' => ['nullable', 'integer', 'exists:regular_sales_invoices,id'],
            'counter_sales_invoice_id' => ['nullable', 'integer', 'exists:counter_sales_invoices,id'],
            'payment_mode_id' => ['nullable', 'integer', 'exists:payment_modes,id'],
            'bank_id' => ['nullable', 'integer', 'exists:banks,id'],
            'received_by_id' => ['nullable', 'integer', 'exists:employees,id'],
            'advance_receipt_id' => ['nullable', 'integer', 'exists:regular_receipts,id'],
            'amount' => ['numeric', 'min:0'],
            'difference_amount' => ['numeric', 'min:0'],
            'receipt_difference_reason_id' => ['nullable', 'integer', 'exists:receipt_difference_reasons,id'],
            'cheque_no' => ['nullable', 'string', 'max:40'],
            'cheque_date' => ['nullable', 'date'],
            'cheque_status' => ['nullable', Rule::in(array_keys(RegularReceipt::chequeStatuses()))],
            'cheque_bounce_reason_id' => ['nullable', 'integer', 'exists:cheque_bounce_reasons,id'],
            'reference_no' => ['nullable', 'string', 'max:60'],
            'cancellation_reason_id' => ['nullable', 'integer', 'exists:receipt_cancellation_reasons,id'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachmentType' => ['nullable', Rule::in(array_keys(RegularReceipt::attachmentTypes()))],
            'attachmentFiles.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
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
                ->orderByDesc('id'),
            searchColumns: ['first_name', 'last_name', 'phone'],
            term: $this->customerSearch,
            selected: $this->customer_id,
            columns: ['id', 'first_name', 'last_name', 'phone'],
            limit: 30,
        )->map(fn ($c) => [
            'id' => $c->id,
            'label' => trim($c->first_name.' '.($c->last_name ?? '')).($c->phone ? ' · '.$c->phone : ''),
        ]);
    }

    #[Computed]
    public function insuranceCompanies()
    {
        return InsuranceCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function regularInvoices()
    {
        return RegularSalesInvoice::query()->orderByDesc('created_at')->limit(100)->get(['id', 'invoice_no']);
    }

    #[Computed]
    public function counterInvoices()
    {
        return CounterSalesInvoice::query()->orderByDesc('created_at')->limit(100)->get(['id', 'invoice_no']);
    }

    #[Computed]
    public function paymentModes()
    {
        return PaymentModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function banks()
    {
        return BankMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function advanceReceipts()
    {
        return RegularReceipt::query()
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->orderByDesc('created_at')->limit(100)->get(['id', 'receipt_no']);
    }

    #[Computed]
    public function differenceReasons()
    {
        return ReceiptDifferenceReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function chequeBounceReasons()
    {
        return ChequeBounceReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function cancellationReasons()
    {
        return ReceiptCancellationReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function existingAttachments()
    {
        if (! $this->editingId) {
            return collect();
        }

        return RegularReceipt::findOrFail($this->editingId)->attachments()
            ->whereNotIn('id', $this->removedAttachmentIds)
            ->get();
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'regular_receipt.update' : 'regular_receipt.create');

        $data = $this->validate();
        unset($data['attachmentFiles'], $data['attachmentType']);

        $existing = $this->editingId ? RegularReceipt::find($this->editingId) : null;
        if ($data['status'] === 'confirmed' && (! $existing || ! $existing->received_at)) {
            $data['received_at'] = now();
        }
        if ($data['cheque_status'] === 'cleared' && (! $existing || ! $existing->cleared_at)) {
            $data['cleared_at'] = now();
        }
        if ($data['status'] === 'cancelled') {
            $data['cancelled_at'] = now();
        }

        foreach (['cheque_no', 'reference_no', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $receipt = DB::transaction(function () use ($data, $isCreate) {
            if ($isCreate) {
                $row = RegularReceipt::create($data);
                $this->editingId = $row->id;
                $this->receipt_no = $row->fresh()->receipt_no;
            } else {
                $row = RegularReceipt::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncAttachments($row);

            return $row;
        });

        $this->attachmentFiles = [];
        $this->removedAttachmentIds = [];

        Flux::toast(text: 'Receipt '.$receipt->fresh()->receipt_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        if ($isCreate) {
            return redirect()->route('regular-receipt.edit', $receipt->id);
        }

        return redirect()->route('regular-receipt.index');
    }

    protected function syncAttachments(RegularReceipt $receipt): void
    {
        if ($this->removedAttachmentIds) {
            foreach ($receipt->attachments()->whereIn('id', $this->removedAttachmentIds)->get() as $att) {
                Storage::disk('public')->delete($att->path);
                $att->delete();
            }
        }

        foreach ($this->attachmentFiles as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }
            $receipt->attachments()->create([
                'attachment_type' => $this->attachmentType,
                'path' => $file->store("regular-receipts/{$receipt->id}/attachments", 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        }
    }

    public function render()
    {
        return view('regular-receipt::edit');
    }
}
