@php($OLI = \App\Modules\OutsideLabourInquiry\Models\OutsideLabourInquiry::class)
<div>
    <form wire:submit="save" class="max-w-3xl">
        <div class="mb-8">
            <flux:link :href="route('outside-labour-inquiry.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Outside Labour Inquiries
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($inquiry_no ?: 'Edit Inquiry') : 'New Outside Labour Inquiry' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Inquire an outside contractor/vendor for charge and availability on work not done in-house.</flux:text>
        </div>

        <flux:separator />

        {{-- DETAILS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Inquiry</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Type of work, department and who's raising it.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="inquiry_type_id" variant="listbox" searchable label="Inquiry Type" placeholder="Denting / Painting / …" required autofocus>
                        @foreach ($this->inquiryTypes as $t)
                            <flux:select.option :value="$t->id" wire:key="it-{{ $t->id }}">{{ $t->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="priority_id" variant="listbox" clearable label="Priority" placeholder="Normal / High / Urgent">
                        @foreach ($this->priorities as $p)
                            <flux:select.option :value="$p->id" wire:key="pr-{{ $p->id }}">{{ $p->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable searchable label="Department" placeholder="Optional">
                        @foreach ($this->departments as $d)
                            <flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="employee_id" variant="listbox" clearable searchable label="Raised By" placeholder="Advisor / staff">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="em-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- CONTEXT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Vehicle & Contractor</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Job card, customer, vehicle and the vendor being inquired.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="Link a job card…">
                        <x-slot name="search">
                            <flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" />
                        </x-slot>
                        @foreach ($this->jobCards as $jc)
                            <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="vendor_id" variant="listbox" searchable clearable :filter="false" label="Vendor / Contractor" placeholder="Who is being inquired…">
                        <x-slot name="search">
                            <flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Search vendor…" />
                        </x-slot>
                        @foreach ($this->vendors as $v)
                            <flux:select.option :value="$v->id" wire:key="vn-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="customer_id" variant="listbox" searchable clearable :filter="false" label="Customer" placeholder="Search customer…">
                        <x-slot name="search">
                            <flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Name or phone…" />
                        </x-slot>
                        @foreach ($this->customers as $c)
                            <flux:select.option :value="$c->id" wire:key="cu-{{ $c->id }}">{{ $c->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="customer_vehicle_id" variant="listbox" searchable clearable :filter="false" label="Vehicle" placeholder="Registration no…">
                        <x-slot name="search">
                            <flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Search reg no…" />
                        </x-slot>
                        @foreach ($this->vehicles as $veh)
                            <flux:select.option :value="$veh->id" wire:key="vh-{{ $veh->id }}">{{ $veh->registration_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="hsn_id" variant="listbox" searchable clearable :filter="false" label="HSN / SAC" placeholder="Billing code…">
                        <x-slot name="search">
                            <flux:select.search wire:model.live.debounce.250ms="hsnSearch" placeholder="Search code…" />
                        </x-slot>
                        @foreach ($this->hsnCodes as $h)
                            <flux:select.option :value="$h->id" wire:key="hsn-{{ $h->id }}">{{ $h->code }} · {{ rtrim(rtrim(number_format((float) $h->gst_percent, 2), '0'), '.') }}%</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="tax_id" variant="listbox" clearable label="Tax Rate" placeholder="GST slab…">
                        @foreach ($this->taxes as $tx)
                            <flux:select.option :value="$tx->id" wire:key="tx-{{ $tx->id }}">{{ $tx->name }} ({{ rtrim(rtrim(number_format((float) $tx->gst_percent, 2), '0'), '.') }}%)</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- SCOPE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Work Scope</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Labour service, job description or complaint — what the contractor is asked to do.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addScope">Add scope</flux:button>
                </div>

                @foreach ($scopes as $i => $scope)
                    <div wire:key="scope-{{ $i }}" class="space-y-2 p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr_1fr_auto] gap-2 items-end">
                            <flux:select wire:model="scopes.{{ $i }}.labour_id" variant="listbox" size="sm" searchable clearable label="Labour" placeholder="Optional…">
                                @foreach ($this->labours as $l)
                                    <flux:select.option :value="$l->id" wire:key="lab-{{ $i }}-{{ $l->id }}">{{ $l->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="scopes.{{ $i }}.job_description_id" variant="listbox" size="sm" searchable clearable label="Job Description" placeholder="Optional…">
                                @foreach ($this->jobDescriptions as $j)
                                    <flux:select.option :value="$j->id" wire:key="jd-{{ $i }}-{{ $j->id }}">{{ $j->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="scopes.{{ $i }}.complaint_type_id" variant="listbox" size="sm" searchable clearable label="Complaint" placeholder="Optional…">
                                @foreach ($this->complaintTypes as $ct)
                                    <flux:select.option :value="$ct->id" wire:key="cmp-{{ $i }}-{{ $ct->id }}">{{ $ct->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:button type="button" variant="ghost" icon="trash" wire:click="removeScope({{ $i }})" />
                        </div>
                        <flux:input wire:model="scopes.{{ $i }}.description" size="sm" placeholder="e.g. FULL BODY DENTING & REPAINT" required />
                        <flux:error name="scopes.{{ $i }}.description" />
                    </div>
                @endforeach
            </div>
        </section>

        <flux:separator />

        {{-- TAT & FOLLOW-UP --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Turnaround & Follow-up</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Promised dates and reminder config. No messages are sent — this is configuration only.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="communication_mode" variant="listbox" clearable label="Communication Mode" placeholder="How the inquiry was sent">
                        @foreach ($OLI::communicationModes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="tat_option" variant="listbox" clearable label="Turnaround (TAT)" placeholder="Expected turnaround">
                        @foreach ($OLI::tatOptions() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div x-show="$wire.tat_option === 'custom'" x-cloak>
                    <flux:input wire:model="tat_custom_days" type="number" min="1" max="365" label="Custom TAT (days)" class="md:max-w-xs" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="grid grid-cols-2 gap-2">
                        <flux:date-picker wire:model="promised_from" label="Promised From" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                        <flux:time-picker wire:model="promised_from_time" label="Time" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <flux:date-picker wire:model="promised_to" label="Promised To" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                        <flux:time-picker wire:model="promised_to_time" label="Time" />
                    </div>
                </div>
                <flux:error name="promised_to" />

                <flux:separator variant="subtle" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="reminder_frequency" variant="listbox" clearable label="Auto Reminder Frequency" placeholder="Off">
                        @foreach ($OLI::reminderFrequencies() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="follow_up_mode_id" variant="listbox" clearable label="Follow-up Mode" placeholder="Call / SMS / WhatsApp…">
                        @foreach ($this->followUpModes as $fm)
                            <flux:select.option :value="$fm->id" wire:key="fm-{{ $fm->id }}">{{ $fm->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div x-show="$wire.reminder_frequency === 'custom'" x-cloak>
                    <flux:input wire:model="reminder_custom_days" type="number" min="1" max="90" label="Custom reminder (every N days)" class="md:max-w-xs" />
                </div>

                <flux:select wire:model="notification_stage" variant="listbox" clearable label="Notification Template" placeholder="Which templated stage" description="Configured, not sent.">
                    @foreach ($OLI::notificationStages() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </section>

        <flux:separator />

        {{-- ATTACHMENTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Photos & Attachments</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Evidence photos (before/after, damage, fault) and documents (PDF/image), e.g. the vendor quote.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                </div>

                @forelse ($attachments as $i => $att)
                    <div wire:key="att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <flux:select wire:model="attachments.{{ $i }}.photo_type_id" variant="listbox" size="sm" searchable clearable label="Type" placeholder="Document / evidence type…">
                            @foreach ($this->photoTypes as $pt)
                                <flux:select.option :value="$pt->id" wire:key="pt-{{ $i }}-{{ $pt->id }}">{{ $pt->group }} · {{ $pt->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <div>
                            <flux:input type="file" wire:model="attachmentFiles.{{ $i }}" size="sm" label="File" accept=".jpg,.jpeg,.png,.webp,.pdf" />
                            @if (! empty($att['path']))
                                <flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>
                            @endif
                            <flux:error name="attachmentFiles.{{ $i }}" />
                        </div>
                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                        No files yet. Click <span class="font-medium">Add file</span> to attach a photo or PDF.
                    </div>
                @endforelse
            </div>
        </section>

        <flux:separator />

        {{-- STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Status</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Inquiry lifecycle and reasons.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <flux:select wire:model.live="status" variant="listbox" label="Inquiry Status" required>
                    @foreach ($OLI::statuses() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div x-show="$wire.status === 'rejected'" x-cloak>
                    <flux:select wire:model="rejection_reason_id" variant="listbox" clearable label="Rejection Reason" placeholder="Why the contractor rejected it">
                        @foreach ($this->rejectionReasons as $rr)
                            <flux:select.option :value="$rr->id" wire:key="rr-{{ $rr->id }}">{{ $rr->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="rejection_reason_id" />
                </div>

                <flux:select wire:model="revision_reason_id" variant="listbox" clearable label="Revision Reason" placeholder="If this inquiry was revised">
                    @foreach ($this->revisionReasons as $vr)
                        <flux:select.option :value="$vr->id" wire:key="vr-{{ $vr->id }}">{{ $vr->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="notes" label="Notes" placeholder="Anything the team should know about this inquiry." rows="2" />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('outside-labour-inquiry.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create inquiry' }}</flux:button>
        </div>
    </form>
</div>
