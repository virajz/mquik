@use(App\Modules\RegularPayment\Models\RegularPayment)
<div class="max-w-7xl">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:link :href="route('regular-payment.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Regular Payments
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? 'Payment '.$payment_no : 'New Regular Payment' }}</flux:heading>
        </div>
        @if ($editingId)
            <div class="flex items-center gap-2">
                <flux:badge size="lg" class="font-mono">₹ {{ number_format((float) $amount, 2) }}</flux:badge>
                @if ($cheque_status)
                    <flux:badge :color="match ($cheque_status) {
                        'cleared' => 'lime', 'bounced' => 'red', 'cancelled' => 'zinc', default => 'sky',
                    }" size="lg">Cheque: {{ RegularPayment::chequeStatuses()[$cheque_status] }}</flux:badge>
                @endif
                <flux:badge :color="match ($status) {
                    'paid' => 'green', 'on_hold' => 'amber', 'cancelled' => 'red', default => 'sky',
                }" size="lg">{{ RegularPayment::statuses()[$status] }}</flux:badge>
            </div>
        @endif
    </div>

    <flux:separator class="mb-6" />

    <form wire:submit="save" novalidate>
        @if (! $editingId)
            @include('regular-payment::partials.section-details')
            <flux:callout class="mt-6" icon="information-circle" variant="secondary">
                <flux:callout.text>Create the payment first — cheque tracking &amp; attachments unlock once it exists. Tip: open from a purchase entry to prefill the vendor &amp; amount.</flux:callout.text>
            </flux:callout>
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('regular-payment.index')" variant="ghost" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Create Payment</flux:button>
            </div>
        @else
            <flux:tab.group>
                <flux:tabs wire:model="activeTab">
                    <flux:tab name="details" icon="banknotes">Payment</flux:tab>
                    <flux:tab name="cheque" icon="document-check">Cheque &amp; Hold</flux:tab>
                    <flux:tab name="attachments" icon="paper-clip">Attachments</flux:tab>
                </flux:tabs>

                <flux:tab.panel name="details" class="pt-6">
                    @include('regular-payment::partials.section-details')
                </flux:tab.panel>

                {{-- CHEQUE & HOLD --}}
                <flux:tab.panel name="cheque" class="pt-6">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
                        <div>
                            <flux:heading size="lg">Cheque &amp; Hold</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Payment status, hold &amp; cancellation reasons, and cheque lifecycle.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <flux:select wire:model="status" variant="listbox" label="Payment Status" required>
                                    @foreach (RegularPayment::statuses() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="payment_hold_reason_id" variant="listbox" searchable clearable label="Hold Reason" placeholder="If on hold…">
                                    @foreach ($this->holdReasons as $hr)
                                        <flux:select.option :value="$hr->id" wire:key="hr-{{ $hr->id }}">{{ $hr->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="payment_cancellation_reason_id" variant="listbox" searchable clearable label="Cancellation Reason" placeholder="If cancelled…">
                                    @foreach ($this->cancellationReasons as $cr)
                                        <flux:select.option :value="$cr->id" wire:key="cr-{{ $cr->id }}">{{ $cr->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <flux:separator variant="subtle" />
                            <flux:heading size="sm">Cheque</flux:heading>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <flux:input wire:model="cheque_no" label="Cheque No." placeholder="If cheque…" class:input="font-mono uppercase" />
                                <flux:date-picker locale="en-IN" wire:model="cheque_date" label="Cheque Date" with-today selectable-header fixed-weeks type="input" />
                                <flux:select wire:model="cheque_status" variant="listbox" clearable label="Cheque Status" placeholder="—">
                                    @foreach (RegularPayment::chequeStatuses() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="cheque_bounce_reason_id" variant="listbox" searchable clearable label="Bounce Reason" placeholder="If bounced…">
                                    @foreach ($this->chequeBounceReasons as $br)
                                        <flux:select.option :value="$br->id" wire:key="br-{{ $br->id }}">{{ $br->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Optional notes on the payment." />
                        </div>
                    </section>
                </flux:tab.panel>

                {{-- ATTACHMENTS --}}
                <flux:tab.panel name="attachments" class="pt-6">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
                        <div>
                            <flux:heading size="lg">Attachments</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Cheque copy, UTR screenshot or payment advice.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <flux:select wire:model="attachmentType" variant="listbox" clearable label="Attachment Type" placeholder="Tag new uploads…" class="md:max-w-xs">
                                @foreach (RegularPayment::attachmentTypes() as $k => $l)
                                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:file-upload wire:model="attachmentFiles" multiple accept=".pdf,image/*">
                                <flux:file-upload.dropzone heading="Drop files here or click to browse" text="PDF, JPG, PNG up to 8MB" />
                            </flux:file-upload>
                            @if ($this->existingAttachments->isNotEmpty() || count($attachmentFiles) > 0)
                                <div class="flex flex-col gap-2">
                                    @foreach ($this->existingAttachments as $att)
                                        <flux:file-item wire:key="att-{{ $att->id }}" :heading="($att->attachment_type ? RegularPayment::attachmentTypes()[$att->attachment_type].' — ' : '').($att->original_name ?? 'File #'.$att->id)" :size="$att->size_bytes ?? 0">
                                            <x-slot name="actions"><flux:file-item.remove wire:click="removeAttachment({{ $att->id }})" /></x-slot>
                                        </flux:file-item>
                                    @endforeach
                                    @foreach ($attachmentFiles as $i => $file)
                                        <flux:file-item wire:key="att-staged-{{ $i }}" :heading="$file->getClientOriginalName()" :size="$file->getSize()" />
                                    @endforeach
                                </div>
                            @endif
                            <flux:error name="attachmentFiles.*" />
                        </div>
                    </section>
                </flux:tab.panel>
            </flux:tab.group>

            <flux:separator class="mt-4" />
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('regular-payment.index')" variant="ghost" wire:navigate>Close</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Save Changes</flux:button>
            </div>
        @endif
    </form>
</div>
