<?php

namespace App\Modules\RegularPayment\Livewire;

use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\ChequeBounceReasonMaster\Models\ChequeBounceReasonMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\PaymentCancellationReasonMaster\Models\PaymentCancellationReasonMaster;
use App\Modules\PaymentHoldReasonMaster\Models\PaymentHoldReasonMaster;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use App\Modules\PurchaseEntry\Models\PurchaseEntry;
use App\Modules\RegularPayment\Models\RegularPayment;
use App\Modules\VendorMaster\Models\VendorMaster;
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
#[Title('Regular Payment')]
class Edit extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $payment_no = null;

    public string $status = 'draft';

    public ?int $vendor_id = null;

    public ?int $paid_by_id = null;

    public ?int $advance_payment_id = null;

    public ?int $purchase_entry_id = null;

    public ?int $payment_mode_id = null;

    public ?int $bank_id = null;

    public float $amount = 0;

    public ?string $cheque_no = null;

    public ?string $cheque_date = null;

    public ?string $cheque_status = null;

    public ?int $cheque_bounce_reason_id = null;

    public ?int $payment_hold_reason_id = null;

    public ?int $payment_cancellation_reason_id = null;

    public ?string $reference_no = null;

    public ?string $notes = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    #[Url(as: 'from-purchase-entry')]
    public ?int $fromPurchaseEntry = null;

    public ?string $attachmentType = null;

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachmentFiles = [];

    /** @var list<int> */
    public array $removedAttachmentIds = [];

    public function mount(?RegularPayment $regularPayment = null): void
    {
        if ($regularPayment && $regularPayment->exists) {
            $this->load($regularPayment);

            return;
        }

        if ($this->fromPurchaseEntry) {
            $pe = PurchaseEntry::find($this->fromPurchaseEntry);
            if ($pe) {
                $this->purchase_entry_id = $pe->id;
                $this->vendor_id = $pe->vendor_id;
                $this->amount = (float) ($pe->grand_total ?? 0);
            }
        }
    }

    protected function load(RegularPayment $p): void
    {
        $this->editingId = $p->id;
        foreach ([
            'payment_no', 'status', 'vendor_id', 'paid_by_id', 'advance_payment_id', 'purchase_entry_id',
            'payment_mode_id', 'bank_id', 'cheque_no', 'cheque_status', 'cheque_bounce_reason_id',
            'payment_hold_reason_id', 'payment_cancellation_reason_id', 'reference_no', 'notes',
        ] as $k) {
            $this->{$k} = $p->{$k};
        }
        $this->amount = (float) $p->amount;
        $this->cheque_date = $p->cheque_date?->format('Y-m-d');
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
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'status' => ['required', Rule::in(array_keys(RegularPayment::statuses()))],
            'paid_by_id' => ['nullable', 'integer', 'exists:employees,id'],
            'advance_payment_id' => ['nullable', 'integer', 'exists:regular_payments,id'],
            'purchase_entry_id' => ['nullable', 'integer', 'exists:purchase_entries,id'],
            'payment_mode_id' => ['nullable', 'integer', 'exists:payment_modes,id'],
            'bank_id' => ['nullable', 'integer', 'exists:banks,id'],
            'amount' => ['numeric', 'min:0'],
            'cheque_no' => ['nullable', 'string', 'max:40'],
            'cheque_date' => ['nullable', 'date'],
            'cheque_status' => ['nullable', Rule::in(array_keys(RegularPayment::chequeStatuses()))],
            'cheque_bounce_reason_id' => ['nullable', 'integer', 'exists:cheque_bounce_reasons,id'],
            'payment_hold_reason_id' => ['nullable', 'integer', 'exists:payment_hold_reasons,id'],
            'payment_cancellation_reason_id' => ['nullable', 'integer', 'exists:payment_cancellation_reasons,id'],
            'reference_no' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachmentType' => ['nullable', Rule::in(array_keys(RegularPayment::attachmentTypes()))],
            'attachmentFiles.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
        ];
    }

    #[Computed]
    public function vendors()
    {
        return VendorMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function purchaseEntries()
    {
        return PurchaseEntry::query()->orderByDesc('created_at')->limit(100)->get(['id', 'purchase_no']);
    }

    #[Computed]
    public function advancePayments()
    {
        return RegularPayment::query()
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->orderByDesc('created_at')->limit(100)->get(['id', 'payment_no']);
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
    public function chequeBounceReasons()
    {
        return ChequeBounceReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function holdReasons()
    {
        return PaymentHoldReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function cancellationReasons()
    {
        return PaymentCancellationReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function existingAttachments()
    {
        if (! $this->editingId) {
            return collect();
        }

        return RegularPayment::findOrFail($this->editingId)->attachments()
            ->whereNotIn('id', $this->removedAttachmentIds)
            ->get();
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'regular_payment.update' : 'regular_payment.create');

        $data = $this->validate();
        unset($data['attachmentFiles'], $data['attachmentType']);

        $existing = $this->editingId ? RegularPayment::find($this->editingId) : null;
        if ($data['status'] === 'paid' && (! $existing || ! $existing->paid_at)) {
            $data['paid_at'] = now();
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

        $payment = DB::transaction(function () use ($data, $isCreate) {
            if ($isCreate) {
                $row = RegularPayment::create($data);
                $this->editingId = $row->id;
                $this->payment_no = $row->fresh()->payment_no;
            } else {
                $row = RegularPayment::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncAttachments($row);

            return $row;
        });

        $this->attachmentFiles = [];
        $this->removedAttachmentIds = [];

        Flux::toast(text: 'Payment '.$payment->fresh()->payment_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        if ($isCreate) {
            return redirect()->route('regular-payment.edit', $payment->id);
        }

        return redirect()->route('regular-payment.index');
    }

    protected function syncAttachments(RegularPayment $payment): void
    {
        if ($this->removedAttachmentIds) {
            foreach ($payment->attachments()->whereIn('id', $this->removedAttachmentIds)->get() as $att) {
                Storage::disk('public')->delete($att->path);
                $att->delete();
            }
        }

        foreach ($this->attachmentFiles as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }
            $payment->attachments()->create([
                'attachment_type' => $this->attachmentType,
                'path' => $file->store("regular-payments/{$payment->id}/attachments", 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        }
    }

    public function render()
    {
        return view('regular-payment::edit');
    }
}
