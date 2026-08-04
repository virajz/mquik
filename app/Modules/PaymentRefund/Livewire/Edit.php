<?php

namespace App\Modules\PaymentRefund\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\AdvancePayment\Models\AdvancePayment;
use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\ChequeBounceReasonMaster\Models\ChequeBounceReasonMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GoodsReturnNote\Models\GoodsReturnNote;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\OutsideLabourReturn\Models\OutsideLabourReturn;
use App\Modules\PaymentRefund\Models\PaymentRefund;
use App\Modules\PaymentRefund\Models\PaymentRefundAttachment;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrder;
use App\Support\ChildRows;
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
#[Title('Payment Refund')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $refund_no = null;

    public ?string $refund_against = null;

    public ?string $refund_type = null;

    public string $priority = 'normal';

    public string $status = PaymentRefund::STATUS_REQUESTED;

    public ?int $vendor_id = null;

    public ?int $refund_by_id = null;

    public ?int $store_incharge_id = null;

    public ?int $advisor_id = null;

    public ?int $advance_payment_id = null;

    public ?int $vendor_purchase_order_id = null;

    public ?int $goods_return_note_id = null;

    public ?int $outside_labour_return_id = null;

    public ?int $job_card_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?string $regular_payment_reference = null;

    public ?string $purchase_invoice_reference = null;

    public ?string $refund_mode = null;

    public ?int $bank_id = null;

    public ?string $reference_no = null;

    public ?string $cheque_no = null;

    public ?string $cheque_date = null;

    public ?string $cheque_status = null;

    public ?int $cheque_bounce_reason_id = null;

    public ?string $rejection_reason = null;

    public ?string $hold_reason = null;

    public ?string $cancellation_reason = null;

    public ?float $amount = null;

    public ?string $notes = null;

    public string $vendorSearch = '';

    public string $advanceSearch = '';

    public string $poSearch = '';

    public string $grnSearch = '';

    public string $olrSearch = '';

    public string $jobCardSearch = '';

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?PaymentRefund $paymentRefund = null): void
    {
        if ($paymentRefund && $paymentRefund->exists) {
            $this->load($paymentRefund);
        }
    }

    protected function load(PaymentRefund $r): void
    {
        $r->load('attachments');
        $this->editingId = $r->id;
        foreach ([
            'refund_no', 'refund_against', 'refund_type', 'priority', 'status', 'vendor_id', 'refund_by_id',
            'store_incharge_id', 'advisor_id', 'advance_payment_id', 'vendor_purchase_order_id',
            'goods_return_note_id', 'outside_labour_return_id', 'job_card_id', 'customer_id',
            'customer_vehicle_id', 'regular_payment_reference', 'purchase_invoice_reference', 'refund_mode',
            'bank_id', 'reference_no', 'cheque_no', 'cheque_status', 'cheque_bounce_reason_id',
            'rejection_reason', 'hold_reason', 'cancellation_reason', 'notes',
        ] as $k) {
            $this->{$k} = $r->{$k};
        }
        $this->amount = $r->amount === null ? null : (float) $r->amount;
        $this->cheque_date = $r->cheque_date?->format('Y-m-d');

        $this->attachments = $r->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'refund_against' => ['nullable', Rule::in(array_keys(PaymentRefund::refundAgainstOptions()))],
            'refund_type' => ['nullable', Rule::in(array_keys(PaymentRefund::refundTypes()))],
            'priority' => ['required', Rule::in(array_keys(PaymentRefund::priorities()))],
            'status' => ['required', Rule::in(array_keys(PaymentRefund::statuses()))],
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'refund_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'store_incharge_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'advisor_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'advance_payment_id' => ['nullable', 'integer', Rule::exists('advance_payments', 'id')],
            'vendor_purchase_order_id' => ['nullable', 'integer', Rule::exists('vendor_purchase_orders', 'id')],
            'goods_return_note_id' => ['nullable', 'integer', Rule::exists('goods_return_notes', 'id')],
            'outside_labour_return_id' => ['nullable', 'integer', Rule::exists('outside_labour_returns', 'id')],
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'regular_payment_reference' => ['nullable', 'string', 'max:255'],
            'purchase_invoice_reference' => ['nullable', 'string', 'max:255'],
            'refund_mode' => ['nullable', Rule::in(array_keys(PaymentRefund::refundModes()))],
            'bank_id' => ['nullable', 'integer', Rule::exists('banks', 'id')],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'cheque_no' => ['nullable', 'string', 'max:50'],
            'cheque_date' => ['nullable', 'date'],
            'cheque_status' => ['nullable', Rule::in(array_keys(PaymentRefund::chequeStatuses()))],
            'cheque_bounce_reason_id' => ['nullable', 'integer', Rule::exists('cheque_bounce_reasons', 'id'), Rule::requiredIf(fn () => $this->cheque_status === 'bounce')],
            'rejection_reason' => ['nullable', Rule::in(array_keys(PaymentRefund::rejectionReasons())), Rule::requiredIf(fn () => $this->status === PaymentRefund::STATUS_REJECTED)],
            'hold_reason' => ['nullable', Rule::in(array_keys(PaymentRefund::holdReasons())), Rule::requiredIf(fn () => $this->status === PaymentRefund::STATUS_ON_HOLD)],
            'cancellation_reason' => ['nullable', Rule::in(array_keys(PaymentRefund::cancellationReasons())), Rule::requiredIf(fn () => $this->status === PaymentRefund::STATUS_CANCELLED)],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(PaymentRefundAttachment::attachmentTypes()))],
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

    /** Pull the amount from the linked advance payment. */
    public function updatedAdvancePaymentId($value): void
    {
        if (! $value) {
            return;
        }

        $payment = AdvancePayment::find($value);
        if ($payment && $payment->amount !== null && $this->amount === null) {
            $this->amount = (float) $payment->amount;
        }
    }

    // ---- Pickers -----------------------------------------------------------

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
    public function vendors()
    {
        return $this->pickerOptions(
            query: VendorMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'vendor_code'], term: $this->vendorSearch, selected: $this->vendor_id, columns: ['id', 'name'], limit: 30,
        );
    }

    #[Computed]
    public function advancePayments()
    {
        return $this->pickerOptions(
            query: AdvancePayment::query()->latest('id'),
            searchColumns: ['payment_no'], term: $this->advanceSearch, selected: $this->advance_payment_id, columns: ['id', 'payment_no'], limit: 30,
        );
    }

    #[Computed]
    public function purchaseOrders()
    {
        return $this->pickerOptions(
            query: VendorPurchaseOrder::query()->latest('id'),
            searchColumns: ['po_no'], term: $this->poSearch, selected: $this->vendor_purchase_order_id, columns: ['id', 'po_no'], limit: 30,
        );
    }

    #[Computed]
    public function goodsReturnNotes()
    {
        return $this->pickerOptions(
            query: GoodsReturnNote::query()->latest('id'),
            searchColumns: ['return_no'], term: $this->grnSearch, selected: $this->goods_return_note_id, columns: ['id', 'return_no'], limit: 30,
        );
    }

    #[Computed]
    public function outsideLabourReturns()
    {
        return $this->pickerOptions(
            query: OutsideLabourReturn::query()->latest('id'),
            searchColumns: ['return_no'], term: $this->olrSearch, selected: $this->outside_labour_return_id, columns: ['id', 'return_no'], limit: 30,
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

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'payment_refund.update' : 'payment_refund.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        foreach (['reference_no', 'cheque_no', 'regular_payment_reference', 'purchase_invoice_reference', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $refund = DB::transaction(function () use ($data, $attachments, $isCreate) {
            if ($isCreate) {
                $data['requested_at'] = now();
                if ($data['status'] === PaymentRefund::STATUS_REFUNDED) {
                    $data['refunded_at'] = now();
                } elseif ($data['status'] === PaymentRefund::STATUS_REJECTED) {
                    $data['rejected_at'] = now();
                }
                $row = PaymentRefund::create($data);
                $this->editingId = $row->id;
                $this->refund_no = $row->fresh()->refund_no;
            } else {
                $row = PaymentRefund::findOrFail($this->editingId);
                if ($data['status'] === PaymentRefund::STATUS_REFUNDED && $row->refunded_at === null) {
                    $data['refunded_at'] = now();
                }
                if ($data['status'] === PaymentRefund::STATUS_REJECTED && $row->rejected_at === null) {
                    $data['rejected_at'] = now();
                }
                $row->update($data);
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Payment refund '.$refund->fresh()->refund_no.($isCreate ? ' recorded.' : ' updated.'), variant: 'success');

        return redirect()->route('payment-refund.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(PaymentRefund $refund, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('payment-refunds/'.$refund->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = ChildRows::upsert($refund->attachments(), $row['id'] ?? null,
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $refund->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('payment-refund::edit');
    }
}
